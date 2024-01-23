<?php

namespace portalium\workspace\components;

use Yii;
use yii\base\Component;
use portalium\workspace\models\WorkspaceUser;
use portalium\workspace\Module;

class Workspace extends Component
{
    
    public function checkOwner($id_workspace)
    {
        $activeWorkspaceId = Yii::$app->workspace->id;
        if (Yii::$app->user->can('workspaceWorkspaceFullAccess', ['id_module' => 'workspace'])) {
            return true;
        }

        if ($activeWorkspaceId) {
            if ($id_workspace == $activeWorkspaceId) {
                return true;
            }
        }
        return false;
    }

    public static function getAvailableRoles($params = [])
    {
        $module = isset($params['module']) ? $params['module'] : null;
        if (!$module) {
            return [];
        }
        $availableRoles = Yii::$app->setting->getValue('workspace::available_roles');
        if (isset($availableRoles[$module])) {
            $availableRoles = $availableRoles[$module];
        } else {
            $availableRoles = [];
        }
        $roles = [];
        //add select
        // $roles[] = ['name' => Module::t('Select'), 'value' => ''];
        foreach (Yii::$app->authManager->getRoles() as $role) {
            if (in_array($role->name, $availableRoles)) {
                $roles[] = $role;
            }
        }
        return $roles;
    }

    public function getSupportModules(){
        $allModulesId = Yii::$app->getModules();
        $supportWorkspaceModules = [];
        
        foreach ($allModulesId as $key => $value) {
            if (isset(Yii::$app->getModule($key)->className()::$supportWorkspace) && Yii::$app->getModule($key)->className()::$supportWorkspace) {
                $supportWorkspaceModules[$key] = Yii::$app->getModule($key)->className()::$supportWorkspace;
            }
        }

        return $supportWorkspaceModules;
    }

    public function getId()
    {
        $workspace = WorkspaceUser::find()
            ->where(['id_user' => Yii::$app->user->id])
            ->andWhere(['status' => WorkspaceUser::STATUS_ACTIVE])
            ->one();
        if ($workspace) {
            return $workspace->id_workspace;
        }
        return null;
    }

    public function checkSupportRoles()
    {
        $supportWorkspaceModules = $this->getSupportModules();
        
        foreach ($supportWorkspaceModules as $key => $value) {
            try {
                $role = Yii::$app->setting->getValue($key . '::workspace::admin_role');
                $defaultRole = Yii::$app->setting->getValue($key . '::workspace::default_role');
                if (!$role || !$defaultRole) {
                    
                    return false;
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                return false;
            }
        }
        return true;
    }

}
