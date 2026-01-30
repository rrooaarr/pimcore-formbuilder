<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace FormBuilderBundle\Tool;

use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LocaleDataMapper
{
    public function __construct(
        private readonly LocaleServiceInterface $localeService,
        #[Autowire('%pimcore.translations.default_locale%')]
        private readonly ?string $defaultLocale = null
    ) {
    }

    public function mapHref(string $locale, array $data): mixed
    {
        // current locale found
        if (isset($data[$locale]) && !empty($data[$locale]['id'])) {
            return $data[$locale]['id'];
        }

        // search for fallback locale
        $fallbackLanguages = $this->localeService->getFallbackLanguages($locale);
        foreach ($fallbackLanguages as $fallbackLanguage) {
            if (isset($data[$fallbackLanguage]) && !empty($data[$fallbackLanguage]['id'])) {
                return $data[$fallbackLanguage]['id'];
            }
        }

        // search for default locale
        if ($this->defaultLocale !== null && isset($data[$this->defaultLocale]) && !empty($data[$this->defaultLocale]['id'])) {
            return $data[$this->defaultLocale]['id'];
        }

        //no locale found. use the first one.
        $firstElement = reset($data);

        return $firstElement['id'];
    }

    public function mapMultiDimensional(string $requestedLocale, string $identifier, bool $isHref, array $data): array
    {
        $blockGenerator = static function ($locale) use ($isHref, $data, $identifier) {
            if ($isHref === true) {
                return isset($data[$locale][$identifier]['id']);
            }

            return isset($data[$locale][$identifier]);
        };

        if ($blockGenerator($requestedLocale) === true) {
            return $data[$requestedLocale];
        }

        // search for fallback locale
        $fallbackLanguages = $this->localeService->getFallbackLanguages($requestedLocale);
        foreach ($fallbackLanguages as $fallbackLanguage) {
            if ($blockGenerator($fallbackLanguage) === true) {
                return $data[$fallbackLanguage];
            }
        }

        // search for default locale
        if ($this->defaultLocale !== null && $blockGenerator($this->defaultLocale) === true) {
            return $data[$this->defaultLocale];
        }

        //no locale found. use the first one.
        return reset($data);
    }
}
