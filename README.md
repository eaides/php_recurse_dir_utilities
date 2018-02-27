# php_recurse_dir_utilities
set use as:

    use eaides\php_recurse_dir_utilities\php_recurse_dir_utilities;
        or
    use eaides\php_recurse_dir_utilities\php_recurse_dir_utilities as dir_utilities;
    
The purpose of this class is to provide 3 methods that work with files or folders in recursive mode.

# recurse_rmdir($dir_or_file, $debug=false)
this method will remove (when possible) the file or the whole folder provide by $dir_or_file.
     params:
     $dir_or_file   the name of the file or folder to remove.
     $debug         if it is true (by default = false) the copy will print the manes of 
                    the folder/files to remove.
     
     notes: - the process will not fail if any file/folder cannot be deleted.
           
# recurse_copy($src, $dst, $debug=false)
this method will copy a file or a whole folder ($src)  to the destination ($dst).
     params:
     $src           the name of the file or folder to copy FROM.
     $dst           the name to copy TO.
     $debug         if it is true (by default = false) the copy will print the manes of the folder/files 
                    to copy.

     note: - the owner and group of the copy files/folder will be:
           a) if you provide to the class for an owner name and group name, and if the user 
              and group are defined in the system, then those will be set.
           b) else, the owner/group that run the command will be used.
           - the owner and group can be set by the constructor or by appropriate methods (see below).
           
           - if $src is equal to $dst nothing will do.
           - if $dst exists, will try to remove it with the recurse_rmdir method.
           - if the recurse_rmdir cannot remove any file or folder (permission issues) they will 
             remain and if exist any file/folder with the same name, the copy will fail.
           - the process will not fail if an existing destination file/folder cannot be re-write.
           
# is_writable_recursive($dir_or_file, $booleanMode=false)      
this method will check if the file or ALL folder and it's contents are writable.
     params:
     $dir_or_file    the name of the file or folder to check if is writable.
     $booleanMode    a boolean false (default) for an array return or true for boolean return.

     return:
     an array of all the files/folder NOT writable (if the count of the array = 0 then 
     the folder is fully writable).
       or
     a boolean true/false.
     
# setOwnerDefault($owner_default)
this method will set the username to use as 'owner' of the new files/folders (copy).
    params:
    $owner_default  the username to be the 'owner'.

    note: - if the username not exists, the user that runs the command will be used.

# setGroupDefault($group_default)
this method will set the group name to use as 'group' of the new files/folders (copy).
    params:
    $group_default  the group name to be the 'group'.

    note: - if the group name not exists, the group of the user that runs the command will be used.
 
# setPermissionsFiles($permissions_files)
this method will set the wanted permission for any new file created.
    params:
    $permissions_files  the permission to use, in Octal Mode, like 0664.

    note: - the passed permission must be a valid numeric, 
            else the default for the system will be used.
            
# setPermissionsDirs($permissions_dirs)
this method will set the wanted permission for any new folder created.
    params:
    $permissions_dirs  the permission to use, in Octal Mode, like 0775.

    note: - the passed permission must be a valid numeric, 
            else the default for the system will be used.
            
# setNotChangeOwnerAndGroup()
this method will un-set the default user and group, then any new file/folder will use the user/group
of the user that runs the command.

# setNotChangeOwnerAndGroup()
this method will un-set the default permissions for files and for folders, then any new file/folder
will be set with the default permissions for the user that runs the command.

# examples:

<?php

use eaides\php_recurse_dir_utilities\php_recurse_dir_utilities as dir_utilities;

myClass()
{
        /** @var dir_utilities $ud */
        $ud = new dir_utilities();
        $ud->setGroupDefault('developers')->setOwnerDefault('www-data');
        $ud->setPermissionsDirs(0775)->setPermissionsFiles(0664);

        $nonWritables = $ud->is_writable_recursive('/my_folder/to/check');
        $isWritable = $ud->is_writable_recursive('/my_folder/to/check', true);
        
        $ud->recurse_copy('/my_folder/to/check/from', '/my_folder/to/check/to');
        $isWritable = $ud->is_writable_recursive('/my_folder/to/check/to', true); // must be true
        
        $ud->recurse_rmdir('/my_folder/to/check/to', true); // with debug
        $ud->recurse_rmdir('/my_folder/to/check/to');       // without debug
}
