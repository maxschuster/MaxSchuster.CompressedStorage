<?php

namespace MaxSchuster\CompressedStorage\ResourceManagement\Storage;

/*
 * This file is part of the MaxSchuster.CompressedResource package.
 */

use Neos\Flow\Annotations as Flow;

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\WritableFileSystemStorage;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Neos\Flow\ResourceManagement\Storage\Exception as StorageException;

/**
 * A writable file system storage that compresses the resource before storing it on the file system.
 */
class CompressedStorage extends WritableFileSystemStorage
{

    /**
     * Gets the compressed file size of the given resource
     *
     * @param PersistentResource $resource The resource stored in this storage
     * @return int|boolean The compressed file size or FALSE on error
     */
    public function getCompressedSize(PersistentResource $resource) {
        $pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
        return @filesize($pathAndFilename);
    }

    /**
     * Gets the compressed stream of the given resource.
     *
     * @param PersistentResource $resource The resource stored in this storage
     * @return resource|bool A resource pointer or FALSE on error
     */
    public function getCompressedStreamByResource(PersistentResource $resource) {
        $pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
        return $this->getStreamByPath($pathAndFilename, false);
    }

    public function getStreamByResource(PersistentResource $resource)
    {
        $pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
        return $this->getStreamByPath($pathAndFilename, true);
    }

    /**
     * Gets the compressed stream of the given relative path.
     *
     * @param string $relativePath Relative path to the resource
     * @return resource|bool A resource pointer or FALSE on error
     */
    public function getCompressedStreamByResourcePath(string $relativePath)
    {
        $pathAndFilename = $this->path . ltrim($relativePath, '/');
        return $this->getStreamByPath($pathAndFilename, false);
    }

    public function getStreamByResourcePath($relativePath)
    {
        $pathAndFilename = $this->path . ltrim($relativePath, '/');
        return $this->getStreamByPath($pathAndFilename, true);
    }

    /**
     * @param string $pathAndFilename Path to the resource
     * @param bool $decompress TRUE to decompress the resource
     * @return resource|bool A resource pointer or FALSE on error
     */
    protected function getStreamByPath(string $pathAndFilename, bool $decompress)
    {
        if (!file_exists($pathAndFilename)) {
            return false;
        }
        return @fopen($decompress ? "compress.zlib://" . $pathAndFilename : $pathAndFilename, 'rb');
    }

    protected function getStoragePathAndFilenameByHash($sha1Hash)
    {
        return parent::getStoragePathAndFilenameByHash($sha1Hash) . ".gz";
    }

    protected function moveTemporaryFileToFinalDestination($temporaryFile, $finalTargetPathAndFilename)
    {
        $dir = dirname($finalTargetPathAndFilename);
        if (!file_exists($dir)) {
            try {
                Files::createDirectoryRecursively($dir);
            } catch (FilesException $e) {
                throw new StorageException(sprintf('Could not create the target directory "%s"', $dir), 1529004076);
            }
        }

        try {
            $fp = fopen($temporaryFile, 'r');
            if ($fp === false) {
                throw new StorageException('The temporary file of the file import could not be opened.', 1529003501);
            }

            if (file_put_contents("compress.zlib://" . $finalTargetPathAndFilename, $fp, FILE_BINARY) === false) {
                throw new StorageException(sprintf('The compressed file of the file import could not be moved to the final target "%s".', $finalTargetPathAndFilename), 1529003648);
            }
        } finally {
            if ($fp) {
                fclose($fp);
            }
        }
        unlink($temporaryFile);

        $this->fixFilePermissions($finalTargetPathAndFilename);
    }

    protected function importTemporaryFile($temporaryPathAndFileName, $collectionName)
    {
        // apply the original file size instead of compressed file size to resource.
        $origFileSize = filesize($temporaryPathAndFileName);
        $resource = parent::importTemporaryFile($temporaryPathAndFileName, $collectionName);
        $resource->setFileSize($origFileSize);
        return $resource;
    }

}