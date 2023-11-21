<?php
/**
 * Upload component
 *
 * Simplifies the file upload and storage for files added to the filesystem
 *
 * Requires the Input Component
 *
 * @package blesta
 * @subpackage blesta.components.upload
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upload
{
    /**
     * @var array An array of allowed MIME types
     */
    private $allowed_mime_types;

    /**
     * @var int The maximum file size allowed, 0 for unlimited
     */
    private $max_file_size = 0;

    /**
     * @var string The full path to the upload directory, where files should be written
     */
    private $upload_path;

    /**
     * @var array An array of files from the global FILES variable
     */
    private $files = [];

    /**
     * @var array An array of errors to be set
     */
    private $errors = [];

    /**
     * @var array An array of data about the upload
     */
    private $data = [];

    /**
     * @var bool True if the files given have been uploaded, false if they were created instead
     */
    private $uploaded_files = true;


    /**
     * Initialize the Upload
     */
    public function __construct()
    {
        // Load the language file for this component
        Language::loadLang('upload', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load the Input component for getting/setting errors within this object
        Loader::loadComponents($this, ['Input']);

        if (isset($_FILES)) {
            $this->setFiles($_FILES);
        }
    }

    /**
     * Sets the array of files to be considered for handling. This class automatically defaults the set of arrays to
     * the global FILES array
     *
     * @param array $files An array of files from the global FILES array to set. The format of this array must match
     * that the global FILES array exactly
     * @param bool $uploaded_files If true the file was uploaded, false if the file was created and not uploaded
     */
    public function setFiles($files, $uploaded_files = true)
    {
        $this->files = $files;
        $this->uploaded_files = $uploaded_files;
    }

    /**
     * Sets the upload path. The upload path will be verified when files are written. If the upload path is invalid
     * file uploads will result in errors
     *
     * @param string $path The server path to where files are to be written
     * @param bool $create Unused argument (optional)
     */
    public function setUploadPath($path, $create = false)
    {
        $this->upload_path = $path;
    }

    /**
     * Sets the maximum file size in bytes. The server may have its own setting for maximum file sizes, so ensure that
     * this value does not exceede the server's allowed maximum size
     *
     * @param int $bytes The maximum file size in bytes. Set to 0 to use no maximum file size.
     */
    public function setMaxFileSize($bytes = 0)
    {
        $this->max_file_size = $bytes;
    }

    /**
     * Sets the allowed MIME types
     *
     * @param array $types An array of MIME types to allow, set to null to allow all MIME types
     */
    public function setAllowedMimeTypes(array $types = null)
    {
        $this->allowed_mime_types = $types;
    }

    /**
     * Writes one or more files to the file system. Because of the nature of writing files to the disk, if any
     *  file fails execution will continue.
     * Any failed files will be reported by Upload::errors()
     *
     * @param string $file A field name to search the global file variable for
     * @param bool $overwrite Whether or not to overwrite the file if it already exists
     * @param string $file_name The name of the file to use instead of the uploaded file's name, null to use
     *  uploaded file's name
     * @param callback $rename_callback A callback to execute used to rename the file
     * @param int $permissions The permission value in octets, null to default to user permissions
     */
    public function writeFile($file, $overwrite = true, $file_name = null, $rename_callback = null, $permissions = 0644)
    {
        if (isset($this->files[$file]['name'])) {
            if (is_array($this->files[$file]['name'])) {
                foreach ($this->files[$file]['name'] as $key => $value) {
                    $this->write(
                        $this->files[$file],
                        $file,
                        $key,
                        $overwrite,
                        $file_name,
                        $rename_callback,
                        $permissions
                    );
                }
            } else {
                $this->write($this->files[$file], $file, null, $overwrite, $file_name, $rename_callback, $permissions);
            }
        }

        $this->Input->setErrors($this->errors);
    }

    /**
     * Writes multiple files to the file system. Because of the nature of writing files to the disk, if any file fails
     *  execution will continue.
     * Any failed files will be reported by Upload::errors()
     *
     * @param array $files An array of file field names to search the global file variable for
     * @param bool $overwrite Whether or not to overwrite the file if it already exists
     * @param array $file_names The names of the files to use instead of the uploaded file's name, null to use uploaded
     *  file's name
     * @param callback $rename_callback A callback to execute used to rename the file
     * @param int $permissions The permission value in octets, null to default to user permissions
     */
    public function writeFiles(
        array $files,
        $overwrite = true,
        array $file_names = null,
        $rename_callback = null,
        $permissions = 0644
    ) {
        foreach ($files as $i => $file) {
            $this->writeFile(
                $file,
                $overwrite,
                ($file_names ? $file_names[$i] : null),
                $rename_callback,
                $permissions
            );
        }

        $this->Input->setErrors($this->errors);
    }

    /**
     * Writes a file to the file system from the given set files
     *
     * @param array $file An array of file information
     * @param string $key The index in the $file array to fetch from
     * @param string $index The index in the $file array to fetch from (if a multi-upload file array)
     * @param bool $overwrite Whether or not to overwrite the file if it already exists
     * @param string $file_name The name of the file to use instead of the uploaded file's name, null to use uploaded
     *  file's name
     * @param callback $rename_callback A callback to execute used to rename the file
     * @param int $permissions The permission value in octets, null to default to user permissions
     * @return bool True if the file was written, false otherwise
     */
    private function write(
        array $file,
        $key = null,
        $index = null,
        $overwrite = true,
        $file_name = null,
        $rename_callback = null,
        $permissions = 0644
    ) {
        $orig_file_name = $index !== null ? $file['name'][$index] : $file['name'];

        // If a file name is given, ensure it uses either the original file's file extension
        if ($file_name) {
            $orig_ext = strrchr($orig_file_name, '.');
            $ext = strrchr($file_name, '.');

            if (!$ext) {
                $file_name .= $orig_ext;
            } elseif ($ext != $orig_ext) {
                $file_name = substr($file_name, 0, -strlen($ext)) . $orig_ext;
            }
        }
        // If no file name given, use the uploaded file's name
        $new_file_name = $file_name ? $file_name : $orig_file_name;

        // Can not attempt upload if there was no file uploaded
        if (($index !== null ? $file['size'][$index] : $file['size']) == 0) {
            return false;
        }

        // If file size is exceede can not write
        if ($this->max_file_size > 0
            && ($index !== null ? $file['size'][$index] : $file['size']) > $this->max_file_size
        ) {
            $this->errors[$key]['max_file_size'] = Language::_('Upload.!error.max_file_size', true);
            return false;
        }

        // Check if the MIME type is allowed
        if (is_array($this->allowed_mime_types)
            && !in_array($index !== null ? $file['type'][$index] : $file['type'], $this->allowed_mime_types)
        ) {
            $this->errors[$key]['mime_type'] = Language::_(
                'Upload.!error.mime_type',
                true,
                $index !== null ? $file['type'][$index] : $file['type']
            );
            return false;
        }

        // If a callback is defined, execute the callback to rename the file
        if ($rename_callback) {
            $new_file_name = call_user_func_array($rename_callback, [$new_file_name]);
        }

        // Don't write the file if overwriting is disabled and a file with that name already exists
        if (!$overwrite && file_exists($this->upload_path . $new_file_name)) {
            $this->errors[$key]['file_exists'] = Language::_('Upload.!error.file_exists', true);
            return false;
        }

        // Attempt to write the file
        $result = false;
        try {
            if ($this->uploaded_files) {
                $result = move_uploaded_file(
                    $index !== null ? $file['tmp_name'][$index] : $file['tmp_name'],
                    $this->upload_path . $new_file_name
                );
            } else {
                $result = rename(
                    $index !== null ? $file['tmp_name'][$index] : $file['tmp_name'],
                    $this->upload_path . $new_file_name
                );
            }

            if ($result) {
                // Set permissions
                if ($permissions != null) {
                    chmod($this->upload_path . $new_file_name, $permissions);
                }

                $upload_data = [
                    'orig_name' => $orig_file_name,
                    'file_name' => $new_file_name,
                    'file_path' => $this->upload_path,
                    'full_path' => $this->upload_path . $new_file_name,
                    'file_size' => ($index !== null ? $file['size'][$index] : $file['size'])
                ];

                if ($index !== null) {
                    $this->data[$key][$index] = $upload_data;
                } else {
                    $this->data[$key] = $upload_data;
                }
            }
        } catch (Exception $e) {
            // file could not be written
        }

        if (!$result) {
            $this->errors[$key]['write_failed'] = Language::_('Upload.!error.write_failed', true);
        }
        return $result;
    }

    /**
     * Fetches the upload data
     *
     * @return array An array of upload data for each of the uploaded files including:
     * 
     *  - orig_name The original name of the uploaded file
     *  - file_name The new file name
     *  - file_path The path where the file was written
     *  - full_path The full path to the file that was written
     *  - file_size The size of the file that was written (in bytes)
     */
    public function getUploadData()
    {
        // Reset any local errors. They'll still exists in Upload::errors(), so no worries
        $this->errors = [];
        return $this->data;
    }

    /**
     * Recursively creates the upload path if it does not already exists. Also sets permissions to the given set when
     * the directory is created
     *
     * @param string $path The directory path to create
     * @param int $permissions The permission value in octets
     */
    public function createUploadPath($path, $permissions = 0755)
    {
        if (!file_exists($path)) {
            try {
                if (!mkdir($path, $permissions, true)) {
                    $this->Input->setErrors(['path' => ['created' => Language::_('Upload.!error.path_created', true)]]);
                }
            } catch (Exception $e) {
                $this->Input->setErrors(['path' => ['created' => Language::_('Upload.!error.path_created', true)]]);
            }
        }
    }

    /**
     * Returns all errors set
     *
     * @return array An array of errors set in Input
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Returns a suggested file name with a number appended at the end so that it is unique in the upload path
     *
     * @param string $file_name The name of the file to append a count to
     * @return string The suggested file name
     */
    public function appendCount($file_name)
    {
        $new_file_name = $file_name;
        $ext = strrchr($file_name, '.');
        $file_name_no_ext = substr($file_name, 0, -strlen($ext));

        for ($i = 1; file_exists($this->upload_path . $new_file_name); $i++) {
            $new_file_name = $file_name_no_ext . $i . $ext;
        }
        return $new_file_name;
    }

    /**
     * Returns a suggested file name that uses an MD5 hash of the existing file name while preserving the
     *  file extension.
     * For example my_file.txt becomes 3715ac9af3d0d8cb0970e08494034357.txt, that is md5(my_file.txt) with
     *  .txt appended.
     *
     * @return string $file_name The suggested file name
     */
    public function md5($file_name)
    {
        $ext = strrchr($file_name, '.');
        return md5($file_name) . $ext;
    }
}
