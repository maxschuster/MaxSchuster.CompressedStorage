<?php

namespace MaxSchuster\CompressedStorage\Html\Component;

/*
 * This file is part of the MaxSchuster.CompressedResource package.
 */

use Neos\Flow\Annotations as Flow;

use MaxSchuster\CompressedStorage\Exceptions\FileNotFoundException;
use MaxSchuster\CompressedStorage\ResourceManagement\Storage\CompressedStorage;
use MaxSchuster\CompressedStorage\ResourceManagement\Target\CompressedTarget;

use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Exception\AccessDeniedException;

/**
 * A custom component that outputs a compressed resource.
 */
class CompressedTargetComponent implements ComponentInterface
{

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @var array
     */
    protected $options;

    /**
     * CompressedTargetComponent constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }


    /**
     * @param ComponentContext $componentContext
     * @return void
     * @api
     * @throws FileNotFoundException
     * @throws AccessDeniedException
     */
    public function handle(ComponentContext $componentContext)
    {
        $sha1 = $this->getSha1($componentContext);
        $acceptsGzipEncoding = $this->acceptsGzipEncoding($componentContext);

        if (empty($sha1)) {
            return; // let request continue
        }

        $resource = $this->resourceManager->getResourceBySha1($sha1);
        if ($resource === null) {
            throw new FileNotFoundException("The resource " . $sha1 . " was not found!", 1529873864);
        }

        $collection = $this->resourceManager->getCollection($resource->getCollectionName());
        if ($collection === null) {
            throw new FileNotFoundException("The resource collection " . $resource->getCollectionName() . " was not found!", 1529873888);
        }

        $target = $collection->getTarget();
        $storage = $collection->getStorage();
        if (!($target instanceof CompressedTarget) || !($storage instanceof CompressedStorage)) {
            throw new AccessDeniedException("Storage or target not supported", 1529875918);
        }

        $size = $acceptsGzipEncoding ? $storage->getCompressedSize($resource) : $resource->getFileSize();
        $fp = $acceptsGzipEncoding ?
            $storage->getCompressedStreamByResource($resource) : $this->resourceManager->getStreamByResource($resource);

        if (!is_resource($fp)) {
            throw new FileNotFoundException("Could not access the resource", 1529875953);
        }

        // \Neos\Flow\var_dump([$acceptsGzipEncoding, $size]); die;

        $response = $componentContext->getHttpResponse();
        $response->setHeader("Content-Type", $resource->getMediaType());
        $response->setHeader("Content-Length", $size);
        // TODO we can't be sure if it's the right filename
        // $response->setHeader("Content-Disposition", "inline; filename=\"" . $resource->getFilename() . "\"");
        if ($acceptsGzipEncoding) {
            $response->setHeader("Content-Encoding", "gzip");
        }
        $response->sendHeaders();

        fpassthru($fp);

        $componentContext->setParameter(ComponentChain::class, 'cancel', true);
    }

    protected function getSha1(ComponentContext $componentContext): ?string {
        $request = $componentContext->getHttpRequest();
        $uri = $request->getUri()->getPath();
        $prefix = "/" . $this->options['uriPrefix'] . "/";

        if (strpos($uri, $prefix) !== 0) {
            return null;
        }

        $info = substr($uri, strlen($prefix));

        $infoParts = explode("/", $info, 2);
        if (!isset($infoParts[0])) {
            return null;
        }

        return $infoParts[0];
    }

    protected function acceptsGzipEncoding(ComponentContext $componentContext): bool {
        $request = $componentContext->getHttpRequest();
        $acceptEncoding = $request->getHeader("Accept-Encoding");
        if (empty($acceptEncoding)) {
            return false;
        }
        $encodings = array_map('trim', explode(",", $acceptEncoding));
        return in_array('gzip', $encodings);
    }

}