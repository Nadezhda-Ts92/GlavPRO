public static function actionGetZip($documents, $basename = "MainApp_Documents.zip") {
    $zip = new ZipArchive();
    $zipName = File::getPathForUpload() . $basename;

    if (!$zip->open($zipName, ZipArchive::CREATE)) {
        throw new Exception('Failed to create ZIP archive');
    }

    foreach ($documents as $document) {
        $unqnames = $document['unqnames'] ?? [];
        $doc_number = $document['doc_number'] ?? null;
        $type = $document['type'] ?? 'Documents';
        $folderName = $type . " CONTRACT " . $doc_number . " from " . date("d.m.Y");
        $zip->addEmptyDir($folderName);

        foreach ($unqnames as $id) {
            $file = File::findOne(['uniqname' => $id]);

            if (!$file || $file->status == File::STATUS_DELETED) {
                continue;
            }

            if (file_exists($file->getPathLocal())) {
                $zip->addFile($file->getPathLocal(), $folderName . '/' . $file->filename);
                continue;
            }

            if ($file->status == File::STATUS_WAIT || $file->status == File::STATUS_PROCESS || $file->status == File::STATUS_ERROR) {
                Yii::info("\n" . 'Zip. File not found, but the statuses are acceptable. ' . $id . "\n", 's3');
                continue;
            }

            if ($file->status == File::STATUS_OK) {
                $ret = $file->loadFile();
                if ($ret == 'ok') {
                    if (file_exists($file->getPathLocal())) {
                        $zip->addFile($file->getPathLocal(), $folderName . '/' . $file->filename);
                    } else {
                        Yii::info("\n" . 'Zip. File not found, although already uploaded. ' . $file->getCmd() . "\n", 's3');
                    }
                    continue;
                }
                Yii::info("Zip", 'ret', 's3');
            }

            Yii::info("\nZip. Unexpected error. File status: " . $file->status, 's3');
        }
    }

    $zip->close();
    return $zipName;
}
