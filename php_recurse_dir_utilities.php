<?php
/**
 * Created by PhpStorm.
 * User: Ernesto Aides
 * Date: 26/02/18
 * Time: 17:56
 */
namespace eaides\php_recurse_dir_utilities;

class php_recurse_dir_utilities
{

    protected $owner_default = false;
    protected $group_default = false;
    protected $permissions_files = false;
    protected $permissions_dirs = false;
    protected $eol = PHP_EOL;

    /**
     * php_recurse_dir_utilities constructor.
     * @param bool $owner_default
     * @param bool $group_default
     * @param bool $permissions_files
     * @param bool $permissions_dirs
     */
    public function __construct($owner_default=false,$group_default=false,$permissions_files=false,$permissions_dirs=false)
    {
        if ($owner_default) $this->owner_default = $owner_default;
        if ($group_default) $this->group_default = $group_default;
        if ($permissions_files && is_numeric($permissions_files)) $this->permissions_files = $permissions_files;
        if ($permissions_dirs && is_numeric($permissions_dirs)) $this->permissions_dirs = $permissions_dirs;
        if (!$this->isCommandLineInterface())
        {
            $this->eol = '<br>';
        }
    }

    /**
     * @param string $owner_default
     * @return $this
     */
    public function setOwnerDefault($owner_default)
    {
        if ($owner_default) $this->owner_default = $owner_default;
        return $this;
    }

    /**
     * @param string $group_default
     * @return $this
     */
    public function setGroupDefault($group_default)
    {
        if ($group_default) $this->group_default = $group_default;
        return $this;
    }

    /**
     * @param int $permissions_files
     * @return $this
     */
    public function setPermissionsFiles($permissions_files)
    {
        if ($permissions_files && is_numeric($permissions_files)) $this->permissions_files = $permissions_files;
        return $this;
    }

    /**
     * @param int $permissions_dirs
     * @return $this
     */
    public function setPermissionsDirs($permissions_dirs)
    {
        if ($permissions_dirs && is_numeric($permissions_dirs)) $this->permissions_dirs = $permissions_dirs;
        return $this;
    }

    /**
     * @return $this
     */
    public function setNotChangeOwnerAndGroup()
    {
        $this->owner_default = false;
        $this->group_default = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function setNotChangePermissions()
    {
        $this->permissions_dirs = false;
        $this->permissions_files = false;
        return $this;
    }

    /**
     * @brief recurse removes files and non-empty directories
     * @param string $dir_or_file
     * @param bool $debug
     * @return void
     */
    public function recurse_rmdir($dir_or_file, $debug=false)
    {
        if (is_dir($dir_or_file) && !is_link($dir_or_file))
        {
            $files = array_diff(scandir($dir_or_file), array(".", ".."));
            foreach ($files as $file)
            {
                if ($debug)
                {
                    echo 'call recursive for: ' . $dir_or_file . DIRECTORY_SEPARATOR . $file . $this->eol;
                }
                $this->recurse_rmdir($dir_or_file . DIRECTORY_SEPARATOR . $file, $debug);
            }
            if ($debug)
            {
                echo 'try to remove directory: ' . $dir_or_file . $this->eol;
            }
            @rmdir($dir_or_file);
        }
        else
        {
            if ($debug)
            {
                echo 'try to unlink: ' . $dir_or_file . $this->eol;
            }
            $rc = @unlink($dir_or_file);
            if (!$rc && $debug)
            {
                echo '   !! failed to unlink: ' . $dir_or_file . ' !!' . $this->eol;
            }
        }
    }

    /**
     * @brief copies files and non-empty directories (recursive)
     * @param string $src
     * @param string $dst
     * @param bool $debug
     * @return void
     */
    public function recurse_copy($src, $dst, $debug=false)
    {
        if ($src === $dst) return;

        if (file_exists($dst))
        {
            if ($debug)
            {
                echo 'call to recursive remove the destination $dst' . $this->eol;
            }
            $this->recurse_rmdir($dst);
        }
        if (is_link($src))
        {
            if ($debug)
            {
                echo '$src ' . $src . ' is a symlink' . $this->eol;
            }
            @symlink(readlink($src), $dst);
            $this->tryToChangeOwnGrpMod($dst,'f');
        }
        elseif (is_dir($src))
        {
            if ($debug)
            {
                echo '$src ' . $src . ' is a dir, first create the dir' . $this->eol;
            }
            @mkdir($dst);
            $this->tryToChangeOwnGrpMod($dst,'d');
            $files = array_diff(scandir($src), array(".", ".."));
            foreach ($files as $file)
            {
                $fNameLower = strtolower($src . DIRECTORY_SEPARATOR . $file);
                if (strpos($fNameLower, DIRECTORY_SEPARATOR . '.svn') === false)
                {
                    if ($debug)
                    {
                        echo '  second call to recursive copy' . $this->eol;
                    }
                    $this->recurse_copy(
                        $src . DIRECTORY_SEPARATOR . $file,
                        $dst . DIRECTORY_SEPARATOR . $file,
                        $debug
                    );
                }
            }
        }
        elseif (is_file($src))
        {
            $fNameLower = strtolower($src);
            if (strpos($fNameLower, DIRECTORY_SEPARATOR . '.svn') === false)
            {
                if ($debug)
                {
                    echo '$src ' . $src . ' is a file' . $this->eol;
                }
                @copy($src, $dst);
                $this->tryToChangeOwnGrpMod($dst,'f');
            }
        }
    }

    /**
     * @brief check recursive if all files and directories are writable
     *        can return an array of non writable files/folder (use 'a' -default- as $mode)
     *        or can return a boolean (use 'b' as $mode)
     * @param string $dir_or_file
     * @param bool $booleanMode
     * @return array|boolean
     */
    public function is_writable_recursive($dir_or_file, $booleanMode=false)
    {
        $booleanMode = (bool)$booleanMode;

        $non_writables = array();
        if (!file_exists($dir_or_file)) return $non_writables;
        if (is_dir($dir_or_file))
        {
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir_or_file));
            foreach ($rii as $file)
            {
                if (is_link($file))
                {
                    continue;
                }
                if (!is_writable($file))
                {
                    $non_writables[] = $file->getPathname();
                }
            }
        }
        else
        {
            if (!is_writable($dir_or_file))
            {
                $non_writables[] = $dir_or_file;
            }
        }
        if ($booleanMode===true)
        {
            $is_writable = (bool)count($non_writables);
            return !$is_writable;
        }
        return $non_writables;
    }

    /**
     * @param string $file
     * @param string $type
     * @return void
     */
    protected function tryToChangeOwnGrpMod($file, $type)
    {
        if ($type=='d' && $this->permissions_dirs)
        {
            @chmod($file, $this->permissions_dirs);
        }
        else if ($type!='d' && $this->permissions_dirs)
        {
            @chmod($file, $this->permissions_files);
        }
        if ($this->owner_default && $this->owner_default)
        {
            @chown($file, $this->owner_default);
            @chgrp($file, $this->group_default);
        }
    }

    /**
     * @return bool
     */
    protected function isCommandLineInterface()
    {
        if( defined('STDIN') )
        {
            return true;
        }
        if( empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0)
        {
            return true;
        }
        if ( php_sapi_name() === 'cli' ||
            http_response_code()===false ||
            PHP_SAPI === 'cli' ||
            (stristr(PHP_SAPI , 'cgi') and getenv('TERM')) )
        {
            return true;
        }
        return false;
    }
}
