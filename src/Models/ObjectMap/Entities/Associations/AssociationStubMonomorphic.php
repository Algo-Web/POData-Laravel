<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationStubMonomorphic extends AssociationStubBase
{
    /**
     * @param \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase $otherStub
     *
     * @return bool
     */
    public function isCompatible(AssociationStubBase $otherStub) : bool
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }

        if ($this->getThroughFieldChain() !== array_reverse($otherStub->getThroughFieldChain())) {
            return false;
        }

        return ($this->getTargType() === $otherStub->getBaseType())
            && ($this->getBaseType() === $otherStub->getTargType())
            && ($this->getForeignFieldName() === $otherStub->getKeyFieldName())
            && ($this->getKeyFieldName() === $otherStub->getForeignFieldName());
    }
    /**
     * Is this AssociationStub sane?
     */
    public function isOk(): bool
    {

        $isOk = parent::isOk();
        $stringCheck = [$this->targType, $this->foreignFieldName];
        $checkResult = array_filter($stringCheck, [$this, 'checkStringInput']);
        $isOk &= $stringCheck == $checkResult;

        return boolval($isOk);
    }
    /**
     * {@inheritdoc}
     */
    public function morphicType() : string
    {
        return 'monomorphic';
    }
}
