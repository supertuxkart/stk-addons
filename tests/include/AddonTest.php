<?php

class AddonTest extends PHPUnit_Framework_TestCase
{
    public function testAddonFlags()
    {
        $all_flags = F_APPROVED + F_ALPHA + F_BETA + F_RC + F_INVISIBLE + F_DFSG + F_FEATURED + F_LATEST + F_TEX_NOT_POWER_OF_2;

        $this->assertTrue(Addon::isApproved(F_APPROVED));
        $this->assertTrue(Addon::isApproved($all_flags));
        $this->assertFalse(Addon::isApproved(8));

        $this->assertTrue(Addon::isAlpha(F_ALPHA));
        $this->assertTrue(Addon::isAlpha($all_flags));
        $this->assertFalse(Addon::isAlpha(4));

        $this->assertTrue(Addon::isBeta(F_BETA));
        $this->assertTrue(Addon::isBeta($all_flags));
        $this->assertFalse(Addon::isBeta(2));

        $this->assertTrue(Addon::isReleaseCandidate(F_RC));
        $this->assertTrue(Addon::isReleaseCandidate($all_flags));
        $this->assertFalse(Addon::isReleaseCandidate(7));

        $this->assertTrue(Addon::isInvisible(F_INVISIBLE));
        $this->assertTrue(Addon::isInvisible($all_flags));
        $this->assertFalse(Addon::isInvisible(7));

        $this->assertTrue(Addon::isDFSGCompliant(F_DFSG));
        $this->assertTrue(Addon::isDFSGCompliant($all_flags));
        $this->assertFalse(Addon::isDFSGCompliant(7));

        $this->assertTrue(Addon::isFeatured(F_FEATURED));
        $this->assertTrue(Addon::isFeatured($all_flags));
        $this->assertFalse(Addon::isFeatured(7));

        $this->assertTrue(Addon::isLatest(F_LATEST));
        $this->assertTrue(Addon::isLatest($all_flags));
        $this->assertFalse(Addon::isLatest(7));

        $this->assertTrue(Addon::isTextureInvalid(F_TEX_NOT_POWER_OF_2));
        $this->assertTrue(Addon::isTextureInvalid($all_flags));
        $this->assertFalse(Addon::isTextureInvalid(7));
    }
}
 