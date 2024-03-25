<?php
function deleteFilesStartingWith($dir, $prefix) {
    $files = glob($dir . '/*');
    
    foreach ($files as $file) {
        if (is_file($file) && strpos(basename($file), $prefix) === 0) {
            unlink($file);
            echo "Deleted file: $file\n";
        } elseif (is_dir($file)) {
            deleteFilesStartingWith($file, $prefix);
        }
    }
}

$directory = './'; // Specify the directory path here
$prefix = '._'; // Specify the prefix here

deleteFilesStartingWith($directory, $prefix);
