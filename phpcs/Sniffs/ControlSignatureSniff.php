<?php
/**
 * Verifies that control statements conform to their coding standards.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace StkAddons\Sniffs\ControlStructures;

use PHP_CodeSniffer\Sniffs\AbstractPatternSniff;

final class ControlSignatureSniff extends AbstractPatternSniff
{
    /**
     * If true, comments will be ignored if they are found in the code.
     *
     * @var boolean
     */
    public $ignoreComments = true;

    /**
     * Returns the patterns that should be checked.
     *
     * @return string[]
     */
    protected function getPatterns()
    {
        return [
            'doEOL...{EOL...} while (...);EOL',
            'while (...)EOL...{EOL',
            'for (...)EOL...{EOL',
            'if (...)EOL...{EOL',
            'foreach (...)EOL...{EOL',
            '}EOL...else if (...)EOL...{EOL',
            '}EOL...elseif (...)EOL...{EOL',
            '}EOL...elseEOL...{EOL',
            'doEOL...{EOL',
        ];
    }
}
