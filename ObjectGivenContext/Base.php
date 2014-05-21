<?php
/**
 * File containing the Base class for all Given contexts.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\BehatBundle\ObjectGivenContext;

use Behat\Behat\Context\BehatContext;
use eZ\Publish\API\Repository\Values\ValueObject;

abstract class Base extends BehatContext
{
    /**
     * This var is needed to be set when the __destruct is called
     * (if any object was created)
     *
     * @var \eZ\Publish\API\Repository\Repository
     */
    private $repository;

    /**
     * This is the var that will be used by __destruct
     *
     * @var array
     */
    protected $createdObjects = array();

    /**
     * This method is actually needed for the deletion of the created objects
     *
     * @return \eZ\Publish\API\Repository\Repository
     *
     * @see $this->repository
     */
    protected function getRepository()
    {
        if ( empty( $this->repository ) )
        {
            $this->repository = $this->getMainContext()->getRepository();
        }

        return $this->repository;
    }

    /**
     * Destroy/remove/delete all created objects (from given steps)
     */
    public function clean()
    {
        foreach ( $this->createdObjects as $object )
        {
            $this->destroy( $object );
        }

        $this->createdObjects = array();
    }

    /**
     * This is used by the __destruct() function to delete/remove all the objects
     * that were created for testing
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object Object that should be destroyed/removed
     */
    abstract protected function destroy( ValueObject $object );
}
