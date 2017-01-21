<?php
// src/Stsbl/CancelGroupmembershipBundle/Security/Authorization/Voter/ManagementVoter.php
namespace Stsbl\CancelGroupMembershipBundle\Security\Authorization\Voter;

use IServ\ManageBundle\Crud\GroupManage;
use IServ\ManageBundle\Crud\UserManage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Stsbl\CancelGroupMembershipBundle\Security\Privilege;

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
 * Security voter for IServ management section
 * 
 * Addition for the original voter of the IServ Management Section, 
 * which currently does not support 3rd-party bundles.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class ManagementVoter extends Voter
{
    const ATTRIBUTE = 'IS_MANAGER';
    
    /**
     * @var AccessDecsionManagerInterface
     */
    private $desisionManager;
    
    /**
     * The constructor.
     * 
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $desisionManager) 
    {
        $this->desisionManager = $desisionManager;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject) 
    {
        return $attribute === self::ATTRIBUTE;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) 
    {
        if ($attribute === self::ATTRIBUTE) {
            // Check if we have the cancel membership privilege
            if ($this->desisionManager->decide($token, $this->getSupportedPrivileges())) {
                return true;
            }
            
            return false;
        }
    }
    
    /**
     * Get supported privileges
     * 
     * @return string[]
     */
    private function getSupportedPrivileges()
    {
        // also add other IS_MANAGGER privileges, because the original management voter gets may overwritten
        return [Privilege::CANCEL_MEMBERSHIP, GroupManage::PRIVILEGE_CREATE_GROUPS, UserManage::PRIVILEGE_RESET_PASSWORD];
    }
}
