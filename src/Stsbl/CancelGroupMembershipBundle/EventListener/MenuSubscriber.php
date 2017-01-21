<?php
// src/Stsbl/CancelGroupmembershipBundle/EventListener/MenuSubscriber.php
namespace Stsbl\CancelGroupMembershipBundle\EventListener;

use IServ\AdminBundle\EventListener\AdminMenuListenerInterface;
use IServ\CoreBundle\Event\MenuEvent;
use IServ\ManageBundle\Event\MenuEvent as ManageMenuEvent;
use Stsbl\CancelGroupMembershipBundle\Security\Authorization\Voter\CancelVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
 * Description of MenuListener
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class MenuSubscriber implements AdminMenuListenerInterface, EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function onBuildAdminMenu(MenuEvent $event) 
    {        
        // check privilege
        if ($event->getAuthorizationChecker()->isGranted(CancelVoter::ATTRIBUTE)) {
            $menu = $event->getMenu();
            $child = $menu->getChild('users');
            
            $item = $child->addChild('admin_cancel_membership', [
                'route' => 'manage_cancel_membership',
                'label' => _('Cancel group membership')
            ]);
            
            $item->setExtra('icon', 'door-open-in');
            $item->setExtra('icon_style', 'fugue');
        }
    }
    
    /**
     * @param MenuEvent $event
     */
    public function onBuildManageMenu(MenuEvent $event)
    {    
        // check privilege
        if ($event->getAuthorizationChecker()->isGranted(CancelVoter::ATTRIBUTE)) {
            $menu = $event->getMenu();
            
            $item = $menu->addChild('manage_cancel_membership', [
                'route' => 'manage_cancel_membership',
                'label' => _('Cancel group membership')                
            ]);
            
            $item->setExtra('icon', 'door-open-in');
            $item->setExtra('icon_style', 'fugue');
        }
    }
    
    /**
     * @param MenuEvent $event
     */
    public function onBuildUserProfileMenu(MenuEvent $event)
    {
        // check privilege   
        if ($event->getAuthorizationChecker()->isGranted(CancelVoter::ATTRIBUTE)) {
            $menu = $event->getMenu();
            
            $item = $menu->addChild('cancel-membership', [
                    'route' => 'user_cancel_membership',
                    'label' => '.icon-door '._('Cancel group membership'),
            ]);
            
            $item->setLinkAttribute('title', _('Cancel memberships in groups whose membership you are not longer need'));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() 
    {
        return [
            MenuEvent::ADMINMENU => 'onBuildAdminMenu',
            MenuEvent::PROFILEMENU => 'onBuildUserProfileMenu',
            ManageMenuEvent::MANAGEMENTMENU => 'onBuildManageMenu'
        ];
    }

}
