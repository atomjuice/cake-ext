<?php

App::import('Core', 'Auth');

/**
 * Extended request handling component for cakephp controllers, mostly a copy of AuthComponent from CakePHP 1.3
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class AuthenticateComponent extends AuthComponent
{
    /**
     * This overridden identify class is for use with bcrypted passwords only.
     * @param array|Model $user
     * @param array $conditions
     * @return boolean
     */
    public function identify($user = null, array $conditions = null)
    {
        /** condition checking */
        if ($conditions === false) {
            $conditions = array();
        } elseif (is_array($conditions)) {
            $conditions = array_merge((array) $this->userScope, $conditions);
        } else {
            $conditions = $this->userScope;
        }
        $model = & $this->getModel();
        if (empty($user)) {
            $user = $this->user();
            if (empty($user)) {
                return null;
            }
        } elseif (is_object($user) && is_a($user, 'Model')) {
            if (!$user->exists()) {
                return null;
            }
            $user = $user->read();
            $user = $user[$model->alias];
        } elseif (is_array($user) && isset($user[$model->alias])) {
            $user = $user[$model->alias];
        }

        /** validation and record fetching */
        if (is_array($user) && (isset($user[$this->fields['username']]) || isset($user[$model->alias . '.' . $this->fields['username']]))) {
            if (isset($user[$this->fields['username']]) && !empty($user[$this->fields['username']]) && !empty($user[$this->fields['password']])) {
                if (trim($user[$this->fields['username']]) == '=' || trim($user[$this->fields['password']]) == '=') {
                    return false;
                }
                $find = array(
                    $model->alias . '.' . $this->fields['username'] => $user[$this->fields['username']],
                );
            } elseif (isset($user[$model->alias . '.' . $this->fields['username']]) && !empty($user[$model->alias . '.' . $this->fields['username']])) {
                if (trim($user[$model->alias . '.' . $this->fields['username']]) == '=' || trim($user[$model->alias . '.' . $this->fields['password']]) == '=') {
                    return false;
                }
                $find = array(
                    $model->alias . '.' . $this->fields['username'] => $user[$model->alias . '.' . $this->fields['username']],
                );
            } else {
                return false;
            }
            $data = $model->find('first', array(
                'conditions' => array_merge($find, $conditions),
                'recursive'  => 0
            ));
            if (empty($data) || empty($data[$model->alias])) {
                return null;
            }
        } elseif (!empty($user) && is_string($user)) {
            $data = $model->find('first', array(
                'conditions' => array_merge(array($model->escapeField() => $user), $conditions),
            ));
            if (empty($data) || empty($data[$model->alias])) {
                return null;
            }
        }

        /** checking of returned password against user submitted password and removal of hashed value from Model data */
        if (!empty($data) && !empty($data[$model->alias][$this->fields['password']])) {
            if (!password_verify($user[$this->fields['password']], $data[$model->alias][$this->fields['password']])) {
                return null;
            } else {
                unset($data[$model->alias][$this->fields['password']]);
                return $data[$model->alias];
            }
        }
        return null;
    }
}
