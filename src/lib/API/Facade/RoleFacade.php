<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\BehatBundle\API\Facade;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\UserService;

class RoleFacade
{
    private $roleService;

    private $userService;

    private $permissionResolver;

    public function __construct(RoleService $roleService, UserService $userService, PermissionResolver $permissionResolver)
    {
        $this->roleService = $roleService;
        $this->userService = $userService;
        $this->permissionResolver = $permissionResolver;
    }

    public function setUser(string $username)
    {
        $user = $this->userService->loadUserByLogin($username);
        $this->permissionResolver->setCurrentUserReference($user);
    }

    public function createRole($roleName)
    {
        $roleCreateStruct = $this->roleService->newRoleCreateStruct($roleName);
        $roleDraft = $this->roleService->createRole($roleCreateStruct);
        $this->roleService->publishRoleDraft($roleDraft);
    }

    public function addPolicyToRole($roleName, $module, $function, $limitations = null)
    {
        $role = $this->roleService->loadRoleByIdentifier($roleName);
        $roleDraft = $this->roleService->createRoleDraft($role);
        $policyCreateStruct = $this->roleService->newPolicyCreateStruct($module, $function);

        if ($limitations !== null)
        {
            foreach ($limitations as $limitation) {
                $policyCreateStruct->addLimitation($limitation);
            }
        }

        $updatedRoleDraft = $this->roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);

        $this->roleService->publishRoleDraft($updatedRoleDraft);
    }

    public function doesRoleExist($roleName): bool
    {
        try {
            $this->roleService->loadRoleByIdentifier($roleName);
        }
        catch (NotFoundException $e)
        {
            return false;
        }
    }

}