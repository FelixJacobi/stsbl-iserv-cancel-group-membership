<?php
// src/Stsbl/CancelMembershipBundle/Service/CancelMembership.php
namespace Stsbl\CancelGroupMembershipBundle\Service;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Shell;

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
 * Service for canceling group memberships by a group mmeber
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class CancelMembership 
{
    const COMMAND = '/usr/lib/iserv/act_cancel_membership';
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;

    /**
     * The constructor
     */
    public function __construct(Shell $shell, SecurityHandler $securityHandler) 
    {
        $this->shell = $shell;
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Unmembers the logged-in user from a group
     * 
     * @var Group $group
     * 
     */
    public function execute(Group $group)
    {
        $ip = @$_SERVER["REMOTE_ADDR"];
        $fwdIp = preg_replace("/.*,\s*/", "", @$_SERVER["HTTP_X_FORWARDED_FOR"]);
        $act = $this->securityHandler->getUser()->getUsernameForActAdm();
        $groupAct = $group->getAccount();
        $sessionPassword = $this->securityHandler->getSessionPassword();
        
        $this->shell->exec('closefd', ['sudo', self::COMMAND, $act, $groupAct], null, ['IP' => $ip, 'IPFWD' => $fwdIp, 'SESSPW' => $sessionPassword]);
    }
    
    /**
     * Gets output
     * 
     * @return array
     */
    public function getOutput()
    {
        return $this->shell->getOutput();
    }
    
    /**
     * Gets error output
     * 
     * @return array
     */
    public function getError()
    {
        return $this->shell->getError();
    }
    
    /**
     * Gets exit code of last executed command
     * 
     * @return integer
     */
    public function getExitCode()
    {
        return $this->shell->getExitCode();
    }
}
