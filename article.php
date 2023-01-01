<?php
//check if requset method is post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //check if a file was uploaded
    if (isset($_FILES['file']) && !empty($_FILES['file'])) {
        //single file upload
        if (!is_array($_FILES['file'])) {
            //initialize the ziparchive class
            $zip = new ZipArchive();
            //set the name of our zip archive
            $zip_file_name = 'MyFile.zip';
            //create the new zip archive using the $file_name above
            if ($zip->open($zip_file_name, ZipArchive::CREATE) === true) {
                //add the file to the archive
                $zip->addFile($_FILES['file']['tmp_name'], $_FILES['file']['name']);
                //close the archive
                $zip->close();
            } else {
                echo "Couldn't create Zip Archive";
            }
        }
        //multiple files upload
        elseif (is_array($_FILES['file'])) {
            //initialize the ziparchive class
            $zip = new ZipArchive();
            //generate a random key for the zip archive name
            $randKey  = uniqid().rand(0000,9999);
            //zip file name
            $zip_file_name = "$randKey.zip";
            //create the new zip archive using the $file_name above
            if ($zip->open($zip_file_name, ZipArchive::CREATE) === true) {
                //loop through the tmp_name of the files in $_FILES array
                foreach ($_FILES['file']['tmp_name'] as $key => $tmpName) {
                    //the name of the file
                    $file_name = $_FILES['file']['name'][$key];
                    //add the file
                    $zip->addFile($tmpName, $file_name);
                }
                //close the archive
                $zip->close();
            } else {
                echo "Couldn't create Zip Archive";
            }
        }
    }
}
?>

<html>
<form method="post" action="" enctype="multipart/form-data">
    <input type="file" name="file[]" multiple>
    <button type="submit">Submit</button>
</form>

</html>