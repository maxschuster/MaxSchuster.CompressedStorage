<?php

namespace MaxSchuster\CompressedStorage\ResourceManagement\Target;

/*
 * This file is part of the MaxSchuster.CompressedResource package.
 */

use Neos\Flow\Annotations as Flow;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Target\TargetInterface;
use MaxSchuster\CompressedStorage\Html\Component\CompressedTargetComponent;
use Neos\Flow\Http\Request as HttpRequest;

/**
 * A target that does not publish the resources and instead points to CompressedTargetComponent which outputs the
 * resource.
 *
 * @see CompressedTargetComponent
 */
class CompressedTarget implements TargetInterface
{

    /**
     * @var Bootstrap
     * @Flow\Inject
     */
    protected $bootstrap;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Name which identifies this publishing target
     *
     * @var string
     */
    protected $name;

    /**
     * @var HttpRequest
     */
    protected $httpRequest;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="target.package")
     */
    protected $targetPackage;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="target.controller")
     */
    protected $targetController;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="target.action")
     */
    protected $targetAction;

    /**
     * Constructor
     *
     * @param string $name Name of this target instance, according to the resource settings
     * @param array $options Options for this target
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * @param PersistentResource $resource
     * @return string
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function getPublicPersistentResourceUri(PersistentResource $resource)
    {
        $request = $this->getHttpRequest();

        $uri = clone $request->getUri();
        $uri->setPath("/" . $this->options['uriPrefix'] . "/" . $resource->getSha1() . "/" . $resource->getFilename());

        return $uri->__toString();
    }

    public function getPublicStaticResourceUri($relativePathAndFilename)
    {
        throw new \BadMethodCallException();
    }

    /**
     * Returns the name of this target instance
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function publishResource(PersistentResource $resource, CollectionInterface $collection)
    {
        // no op
    }

    public function publishCollection(CollectionInterface $collection)
    {
        // no op
    }

    public function unpublishResource(PersistentResource $resource)
    {
        /// no op
    }

    /**
     * @return HttpRequest
     */
    protected function getHttpRequest()
    {
        if ($this->httpRequest === null) {
            $requestHandler = $this->bootstrap->getActiveRequestHandler();
            if (!($requestHandler instanceof HttpRequestHandlerInterface)) {
                return null;
            }
            $this->httpRequest = $requestHandler->getHttpRequest();
        }
        return $this->httpRequest;
    }
}