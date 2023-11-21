<?php
/**
 * @author         Pierre-Henry Soria <pierrehenrysoria@gmail.com>
 * @copyright      (c) 2017-2019, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; <https://www.gnu.org/licenses/gpl-3.0.en.html>
 */

declare(strict_types=1);

namespace PH7\Eu\Vat\Provider;

use stdClass;

interface Providable
{
    public function getApiUrl(): string;

    public function getResource($sVatNumber, string $sCountryCode): stdClass;
}
