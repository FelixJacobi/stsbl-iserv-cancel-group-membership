<?php
// src/Stsbl/CancelGroupMembershipBundle/Controller/AdminController.php
namespace Stsbl\CancelGroupMembershipBundle\Controller;

use IServ\CoreBundle\Controller\PageController;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Event\NotificationEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\CancelGroupMembershipBundle\Security\Privilege;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Admin Controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class AdminController extends PageController
{
    /**
     * index action
     *
     * @param Request $request
     * @return array
     * @Route("manage/cancel-membership", name="manage_cancel_membership")
     * @Route("profile/cancel-membership", name="user_cancel_membership")
     * @Security("is_granted('CAN_CANCEL_MEMBERSHIPS')")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $form = $this->getForm();
        $form->handleRequest($request);
        
        // move page into admin section for administrators
        if ($this->getUser()->hasRole('ROLE_ADMIN')) {
            $bundle = 'IServAdminBundle';
            $menu = null;
        } else {
            $bundle = 'IServCoreBundle';
            $menu = $this->get('iserv.menu.managment');
        }
        
        $routeName = $request->get('_route');
        if ($routeName === 'user_cancel_membership') {
            $bundle = 'IServCoreBundle';
            $menu = $this->get('iserv.menu.user_profile');
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            /* @var $cancelMembership \Stsbl\CancelGroupMembershipBundle\Service\CancelMembership */
            $cancelMembership = $this->get('stsbl.cancelgroupmembership.service.cancel');
            $cancelMembership->execute($data['group']);
            
            $errors = $cancelMembership->getError();
            $output = $cancelMembership->getOutput();
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->get('iserv.flash')->error($error);
                }
            }
            
            if (count($output) > 0) {
                foreach ($output as $o) {
                    $this->get('iserv.flash')->success($o);
                }
            }
            
            // notification for owner
            $this->notifyOwner($data['group']);
            
            // additional logging
            if (!empty($data['reason'])) {
                $reason = sprintf(' (Begründung: %s)', $data['reason']);
            } else {
                $reason = '';
            }
            
            $groupName = $data['group']->getName();
            
            $this->get('iserv.logger')->write(sprintf('Gruppenmitgliedschaft in Gruppe %s beendet', $groupName).$reason);
            
            // if the user may has no more cancelable memberships redirect him to other page to prevent access denied message
            if ($this->getUser()->hasRole('ROLE_ADMIN')) {
                // if user is admin, back to admin index
                return $this->redirect($this->generateUrl('admin_index'));
            } else {
                // elsewhere to IDesk Start Page
                return $this->redirect($this->generateUrl('index'));
            }
        }
        
        // track path
        if ($bundle === 'IServCoreBundle') {
            $this->addBreadcrumb(_('Administration'), $this->generateUrl('manage_index'));
            $this->addBreadcrumb(_('Cancel group membership'), $this->generateUrl('manage_cancel_membership'));
        } else {
            $this->addBreadcrumb(_('Cancel group membership'), $this->generateUrl('manage_cancel_membership'));
        }
        
        $view = $form->createView();
        
        return ['bundle' => $bundle, 'menu' => $menu, 'form' => $view];
    }
    
    /**
     * Creates form for caneling group memberships
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getForm()
    {
        $builder = $this->get('form.factory')->createNamedBuilder('cancel');
        
        $er = $this->getDoctrine()->getRepository('IServCoreBundle:Group');
        /* @var $groups \IServ\CoreBundle\Entity\Group[] */
        $groups = $er->createFindByFlagQueryBuilder(Privilege::FLAG_CANCELING_ALLOWED)->orderBy('LOWER(g.name)', 'ASC')->getQuery()->getResult();
        $choices = [];
        
        foreach ($groups as $group) {
            if ($group->hasUser($this->getUser())) {
                $choices[] = $group;
            }
        }
        
        $builder
            ->add('group', EntityType::class, [
                'label' => _('Group'),
                'class' => 'IServCoreBundle:Group',
                'select2-icon' => 'legacy-act-group',
                'multiple' => false,
                'required' => false,
                'by_reference' => false,
                'choices' => $choices,
                'attr' => [
                    'help_text' => _('Select the group where you want to cancel the membership.'),
                ],
            ])
            ->add('reason', TextType::class, [
                'label' => _('Reason (optional)'),
                'required' => false,
                    'attr' => [
                    'help_text' => _('Optionally explain, why you don\'t need the membership anymore.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Cancel membership'),
                'buttonClass' => 'btn-success',
                'icon' => 'ok'
            ])
        ;
                
        return $builder->getForm();
    }
    
    /**
     * Notifys the group owner about taht the use rleft the group
     * 
     * @param Group $group
     */
    private function notifyOwner(Group $group)
    {
        $owner = $group->getOwner();
        
        if(is_null($owner)) {
            // no notification, if there is no owner
            return;
        }
        
        $dispatcher = $this->get('event_dispatcher');
        
        if($owner->hasRole('ROLE_ADMIN')) {
            $route = 'admin_group_show';
        } else {
            $route = 'manage_group_show';
        }
        
        $dispatcher->dispatch(NotificationEvent::NAME, new NotificationEvent(
            $owner,
            'cancel-group-membership',
            ['Someone has left one of your groups: %s has left %s', (string)$this->getUser(), (string)$group],
            'door',
            [$route, ['id' => $group->getAccount()]]
        ));
    }
}
