<?php

namespace raincious\Permit;

/**
 * A module use to organize permission data for checking
 *
 * !Experimental!
 *
 * This is a experimental module that you probably don't want to
 * use it in actual work.
 *
 * @copyright       Copyright (C) 2013, Rain Lee
 * @author          Rain Lee <raincious@gmail.com>
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of the FreeBSD Project.
 */
class Could
{
    /**
     * A character to split different levels
     */
    protected $spliter = '';

    /**
     * Flattened permission data
     *
     * This gives a chance to directly check the permission without
     * re-recurse array for build permission data.
     */
    protected $flatPermissions = array();

    /**
     * Data of current permission
     */
    protected $permissions = array();

    /**
     * Constructor of current module
     *
     * @param array $permissionTemplate Array that contains all permission item with default value.
     * @param string $spliter A character to split different levels
     *
     * @return void
     */
    public function __construct(array $permissionTemplate, $spliter = '.')
    {
        $this->spliter = $spliter;

        $this->permissions = $permissionTemplate;

        $this->makeFlattenLink(
            $this->permissions,
            $this->flatPermissions
        );
    }

    /**
     * Make multi dimensional permission data into fatten
     *
     * @param array $targetArray The array of multi dimensional permission data
     * @param array $targetFlatten The array to save result (flatten)
     * @param string $prefix The prefix of key name for the flatting array item
     * @param string $method Method of job
     *
     * @return void
     */
    protected function makeFlattenLink(
        array &$targetArray,
        array &$targetFlatten,
        $prefix = '',
        $method = 'MAKE'
    ) {
        $remain = count($targetArray);
        $currentPrefix = $prefix ? $prefix . $this->spliter : '';

        foreach ($targetArray as $key => $val) {
            if (is_array($val)) {
                if ($this->makeFlattenLink(
                    $targetArray[$key],
                    $targetFlatten,
                    $currentPrefix . $key,
                    $method
                ) <= 0) {
                    $remain--;
                }
            } else {
                switch ($method) {
                    case 'AND':
                        if (isset($targetFlatten['_'][$currentPrefix . $key])) {
                            if ($targetFlatten['_'][$currentPrefix . $key] && $val) {
                                $targetFlatten['_'][$currentPrefix . $key] = $val;
                            } else {
                                $targetFlatten['_'][$currentPrefix . $key] = false;
                            }
                        } else {
                            $targetFlatten['_'][$currentPrefix . $key] = false;
                        }
                        break;

                    case 'OR':
                        if (isset($targetFlatten['_'][$currentPrefix . $key])) {
                            if ($targetFlatten['_'][$currentPrefix . $key] || $val) {
                                $targetFlatten['_'][$currentPrefix . $key] = $val;
                            }
                        } else {
                            $targetFlatten['_'][$currentPrefix . $key] = false;
                        }
                        break;

                    case 'REPLACE':
                        if (isset($targetFlatten['_'][$currentPrefix . $key])) {
                            $targetFlatten['_'][$currentPrefix . $key] = $val;
                        } else {
                            $targetFlatten['_'][$currentPrefix . $key] = false;
                        }
                        break;

                    case 'MAKE':
                        $targetFlatten['_'][$currentPrefix . $key] = &$targetArray[$key];
                        break;

                    default:
                        break;
                }

                if (!$targetFlatten['_'][$currentPrefix . $key]) {
                    $remain--;
                }
            }
        }

        if ($remain > 0) {
            $targetFlatten['_'][$prefix] = true;
        } else {
            $targetFlatten['_'][$prefix] = false;
        }

        $targetFlatten['?'][$prefix] = $remain;

        return $remain;
    }

    /**
     * Set user's permission for permission calculation (replace old data whether true or false)
     *
     * @param array $permissions The multi dimensional permission data for current user
     *
     * @return object Current instance
     */
    public function authorize(array $permissions)
    {
        $this->makeFlattenLink(
            $permissions,
            $this->flatPermissions,
            '',
            'REPLACE'
        );

        return $this;
    }

    /**
     * Set additional permission with limitation (must be both to enable)
     *
     * User have to have both true in current permit and new permit to gain the new permission
     *
     * @param array $permissions The multi dimensional permission data for current user
     *
     * @return object Current instance
     */
    public function both(array $permissions)
    {
        $this->makeFlattenLink(
            $permissions,
            $this->flatPermissions,
            '',
            'AND'
        );

        return $this;
    }

    /**
     * Set additional permission with allowance (Either one is true can enable)
     *
     * User only need one true in current permit or new permit to gain the new permission
     *
     * @param array $permissions The multi dimensional permission data for current user
     *
     * @return object Current instance
     */
    public function either(array $permissions)
    {
        $this->makeFlattenLink(
            $permissions,
            $this->flatPermissions,
            '',
            'OR'
        );

        return $this;
    }

    /**
     * Change current permission by new value
     *
     * @param string $flatKey Key name of the permission in flatten permission table
     * @param mixed $newPermission New permission data
     *
     * @return object Current instance
     */
    public function let($flatKey, $newPermission)
    {
        if (isset($this->flatPermissions['_'][$flatKey])) {
            if ($this->flatPermissions['_'][$flatKey] != $newPermission) {
                $this->flatPermissions['_'][$flatKey] = $newPermission;

                $this->makeFlattenLink(
                    $this->permissions,
                    $this->flatPermissions
                );
            }

            return $this;
        }

        return false;
    }

    /**
     * Change current permissions by new value
     *
     * @param array $newPermissions New permission datas in FlatKey => NewPermission pair
     *
     * @return object Current instance
     */
    public function lets(array $newPermissions)
    {
        foreach ($newPermissions as $flatKey => $newPermission) {
            if (isset($this->flatPermissions['_'][$flatKey])) {
                $this->flatPermissions['_'][$flatKey] = $newPermission;
            }
        }

        $this->makeFlattenLink(
            $this->permissions,
            $this->flatPermissions
        );

        return $this;
    }

    /**
     * Return user permission by flat key name
     *
     * @param array $flatKey Flat key represent a permission item
     *
     * @return mixed Return current permission data when found, or false for fail
     */
    public function can($flatKey)
    {
        if (isset($this->flatPermissions['_'][$flatKey])) {
            return $this->flatPermissions['_'][$flatKey];
        }

        return false;
    }

    /**
     * Export permission data out
     *
     * @return array Return array of current permission datas
     */
    public function export()
    {
        return array(
            'Flat' => $this->flatPermissions['_'],
            'Raw' => $this->permissions
        );
    }
}
