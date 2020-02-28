<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 8:05 PM.
 */
namespace AlgoWeb\PODataLaravel\Serialisers;

use POData\Common\InvalidOperationException;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;

trait SerialiseNavigationTrait
{
    /**
     * @var RootProjectionNode
     */
    protected $rootNode = null;

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree
     * which describes the current segment.
     *
     * @throws InvalidOperationException
     * @return null|RootProjectionNode|ExpandedProjectionNode
     */
    protected function getCurrentExpandedProjectionNode()
    {
        if (null === $this->rootNode) {
            $this->rootNode = $this->getRequest()->getRootProjectionNode();
        }
        $expandedProjectionNode = $this->rootNode;
        if (null === $expandedProjectionNode) {
            return null;
        }
        $segmentNames = $this->getLightStack();
        $depth        = count($segmentNames);
        // $depth == 1 means serialization of root entry
        //(the resource identified by resource path) is going on,
        //so control won't get into the below for loop.
        //we will directly return the root node,
        //which is 'ExpandedProjectionNode'
        // for resource identified by resource path.
        if (!empty($segmentNames)) {
            for ($i = 1; $i < $depth; ++$i) {
                $segName                = $segmentNames[$i]['prop'];
                $expandedProjectionNode = $expandedProjectionNode->findNode($segName);
                if (null === $expandedProjectionNode) {
                    throw new InvalidOperationException('is_null($expandedProjectionNode)');
                }
                if (!$expandedProjectionNode instanceof ExpandedProjectionNode) {
                    $msg = '$expandedProjectionNode not instanceof ExpandedProjectionNode';
                    throw new InvalidOperationException($msg);
                }
            }
        }

        return $expandedProjectionNode;
    }

    /**
     * Gets collection of projection nodes under the current node.
     *
     * @throws InvalidOperationException
     * @return ProjectionNode[]|ExpandedProjectionNode[]|null List of nodes describing projections for the current
     *                                                        segment, If this method returns null it means no
     *                                                        projections are to be applied and the entire resource for
     *                                                        the current segment should be serialized, If it returns
     *                                                        non-null only the properties described by the returned
     *                                                        projection segments should be serialized
     */
    protected function getProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (null === $expandedProjectionNode || $expandedProjectionNode->canSelectAllProperties()) {
            return null;
        }

        return $expandedProjectionNode->getChildNodes();
    }

    /**
     * Check whether to expand a navigation property or not.
     *
     * @param string $navigationPropertyName Name of navigation property in question
     *
     * @throws InvalidOperationException
     * @return bool                      True if the given navigation should be expanded, otherwise false
     */
    protected function shouldExpandSegment(string $navigationPropertyName)
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (null === $expandedProjectionNode) {
            return false;
        }
        $expandedProjectionNode = $expandedProjectionNode->findNode($navigationPropertyName);

        // null is a valid input to an instanceof call as of PHP 5.6 - will always return false
        return $expandedProjectionNode instanceof ExpandedProjectionNode;
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @throws InvalidOperationException
     * @return RequestDescription
     */
    abstract public function getRequest();

    /**
     * @return array
     */
    abstract protected function getLightStack();
}
