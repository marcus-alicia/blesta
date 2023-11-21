<?php
namespace Blesta\Core\Pricing\Presenter\Build\ServiceChange;

use Blesta\Core\Pricing\Presenter\Build\BuilderFactory;
use Blesta\Core\Pricing\Presenter\Type\ServiceChangePresenter;
use Blesta\Core\Pricing\MetaItem\Meta;
use Blesta\Core\Pricing\MetaItem\MetaItemInterface;
use Blesta\Items\Collection\ItemCollection;
use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Pricing\Modifier\PriceModifierInterface;
use stdClass;

/**
 * Service change builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.ServiceChange
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceChangeBuilder extends AbstractServiceChangeBuilder
{
    // Include the Meta trait for meta methods
    use Meta;

    /**
     * Retrieve a ServiceChangePresenter
     *
     * @param stdClass $service An stdClass object representing the original service, including:
     *
     *  - name The service's name
     *  - qty The service quantity
     *  - override_price The service's override price
     *  - override_currency The service's override currency
     *  - date_renews The service renew date
     *  - options An array of service options, each option an stdClass object containing:
     *      - id The option value ID
     *      - service_id The service ID
     *      - option_pricing_id The option's pricing ID
     *      - qty The option quantity
     *      - option_value The option value
     *      - option_value_name The option value name
     *      - option_id The option's ID
     *      - option_label The option's label
     *      - option_name The name of the option
     *      - option_type The type of option
     *      - option_pricing_term The option's pricing term
     *      - option_pricing_period The option's pricing period
     *      - option_pricing_price The option's pricing price
     *      - option_pricing_setup_fee The option's pricing setup fee
     *      - option_pricing_currency The option's pricing currency
     *  - package_pricing An stdClass object representing the service's package pricing, including:
     *      - id The package pricing ID
     *      - package_id The package ID
     *      - term The pricing term
     *      - period The pricing period
     *      - setup_fee The pricing setup fee
     *      - cancel_fee The pricing cancelation fee
     *      - currency The pricing currency
     *  - package An stdClass object representing the service's package, including:
     *      - name The package name
     *      - taxable 1 or 0, whether the package is taxable
     *      - prorata_day The package pro rata day
     *      - prorata_cutoff The package pro rata cutoff day
     * @param array $vars An array of input data, including:
     *
     *  - pricing_id The ID of the selected package pricing
     *  - qty The new service quantity - default's to current service quantity if not specified
     *  - override_price The new override price
     *  - override_currency The new override currency
     *  - configoptions An array of new config options where each key is the option ID
     *      and the value is the selected option value
     * @param stdClass $package An stdClass object representing the new package being changed to, including:
     *
     *  - id The package ID
     *  - name The package's name
     *  - taxable 1 or 0, whether the package is taxable
     *  - prorata_day The package pro rata day
     *  - prorata_cutoff The package pro rata cutoff day
     * @param stdClass $pricing An stdClass object representing the new
     *  service's package pricing, including:
     *
     *  - id The package pricing ID
     *  - package_id The package ID
     *  - term The pricing term
     *  - period The pricing period
     *  - setup_fee The pricing setup fee
     *  - cancel_fee The pricing cancelation fee
     *  - currency The pricing currency
     * @param array $options An array of stdClass objects representing
     *  all new service package options, each including:
     *
     *  - id The option ID
     *  - label The option label
     *  - name The option name
     *  - type The option type
     *  - values An array of stdClass objects representing each option value, including:
     *      - id The option value ID
     *      - option_id The option ID
     *      - value The option value
     *      - min The minimum value
     *      - max The maximum value
     *      - step The step value
     *      - pricing An array whose first index contains an stdClass object representing
     *          the option value pricing, including:
     *          - id The option value pricing ID
     *          - pricing_id The pricing ID
     *          - option_value_id The option value ID
     *          - term The pricing term
     *          - period The pricing period
     *          - price The option value price
     *          - setup_fee The option value setup fee
     *          - cancel_fee The option value cancelation fee
     *          - currency The option value currency
     * @return ServiceChangePresenter An instance of the ServiceChangePresenter
     */
    public function build(stdClass $service, array $vars, stdClass $package, stdClass $pricing, array $options)
    {
        // Build the service
        $serviceBuilder = $this->serviceBuilder();
        $servicePresenter = $serviceBuilder->build($service);

        // Build the service data
        $serviceDataBuilder = $this->serviceDataBuilder();
        $serviceDataPresenter = $serviceDataBuilder->build($vars, $package, $pricing, $options);

        // Update the service data collection to set matching keys with the other collection
        // so that those items can be merged together
        $oldCollection = $servicePresenter->collection();
        $newDataCollection = $this->combineKeys($oldCollection, $serviceDataPresenter->collection());

        // Update both collections to add new zero'd items to correspond to unpaired
        // items of the other collection so that they can be merged too
        $collections = $this->pairCollections($oldCollection, $newDataCollection);

        // Create an item comparator to use for merging individual matching items (i.e. their price and description)
        $comparator = $this->pricingFactory->itemComparator(
            function ($oldPrice, $newPrice, $oldMeta, $newMeta) {
                return $this->combinePrice($oldPrice, $newPrice, $oldMeta, $newMeta);
            },
            function ($oldMeta, $newMeta) {
                return $this->combineDescription($oldMeta, $newMeta);
            }
        );

        // Merge the two collections, remove fees that don't apply, and remove items that have not changed
        $changeCollection = $this->removeUnchanged(
            $this->removeFees(
                $collections['oldCollection']->merge($collections['newCollection'], $comparator)
            )
        );

        return $this->presenterFactory->serviceChange($changeCollection);
    }

    /**
     * Combines two items via their prices and meta data to determine a new single price
     *
     * @param float $oldPrice The subotatl price (excluding taxes, discounts) of the old item
     * @param float $newPrice The subtotal price (excluding taxes, discounts) of the new item
     * @param Blesta\Items\Collection\ItemCollection|null $oldMeta The old meta data, or null
     * @param Blesta\Items\Collection\ItemCollection|null $newMeta The new meta data, or null
     * @return float The new price
     */
    private function combinePrice($oldPrice, $newPrice, ItemCollection $oldMeta = null, ItemCollection $newMeta = null)
    {
        return $newPrice - $oldPrice;
    }

    /**
     * Combines two items via their meta data to determine a new description
     *
     * @param Blesta\Items\Collection\ItemCollection|null $oldMeta The old meta data, or null
     * @param Blesta\Items\Collection\ItemCollection|null $newMeta The new meta data, or null
     * @return string The combined description
     */
    private function combineDescription(ItemCollection $oldMeta = null, ItemCollection $newMeta = null)
    {
        $description = '';

        // Get the meta data of the two items being combined
        if ($newMeta !== null) {
            $newMeta = $this->getMetaFromCollection($newMeta);
        }

        if ($oldMeta !== null) {
            $oldMeta = $this->getMetaFromCollection($oldMeta);
        }

        // Build a description for the item based on the meta data available
        if ($newMeta && isset($newMeta['_data']) && !empty($newMeta['_data']['state'])) {
            $describer = $this->pricingFactory->description();

            $description = $describer->getDescription($newMeta, $oldMeta);
        }

        return $description;
    }

    /**
     * Pairs items in one collection with items in the other collection for all items that have
     * no matching key (i.e. are not already paired). The paired items are 0-cost items.
     * Also updates the meta data to indicate the state of the pairing.
     *
     * @param ItemPriceCollection $oldCollection A collection
     * @param ItemPriceCollection $newCollection A collection
     * @return array An array containing the old and new collections:
     *
     *  - oldCollection The updated old collection
     *  - newCollection The updated new collection
     */
    private function pairCollections(ItemPriceCollection $oldCollection, ItemPriceCollection $newCollection)
    {
        // Make sure both collections have every item assigned a matching key in the other item
        // so that they may be merged together
        $oldKeys = [];
        $newKeys = [];

        // Hash each key from the collection
        foreach ($oldCollection as $item) {
            $oldKeys[$item->key()] = $item;
        }

        foreach ($newCollection as $item) {
            $newKeys[$item->key()] = $item;
        }

        // Add temporary items to the collection worth nothing such that the key may be used for comparison
        foreach ($oldKeys as $key => $item) {
            $meta = ($item instanceof MetaItemInterface ? $this->getMeta($item) : null);

            if (!array_key_exists($key, $newKeys)) {
                // Add a new ItemPrice with a matching key (removed)
                $newItem = $this->pricingFactory->metaItemPrice(0, 0, $key);

                // Set the meta data from the old item onto the new item and indicate it was removed
                if (($metaItem = $this->makeMetaItem($meta, 'removed'))) {
                    $newItem->attach($metaItem);
                }

                // Set the same discounts and taxes on the new item as the old item.
                // This is necessary to get an accurate before/after combined price considering all
                // previous discounts/taxes and all new discounts/taxes
                foreach ($item->discounts() as $discount) {
                    $newItem->setDiscount($discount);
                }

                // Set each group of taxes exactly as they are set on the item
                foreach ($item->taxes(false) as $tax_group) {
                    call_user_func_array([$newItem, 'setTax'], $tax_group);
                }

                $newCollection->append($newItem);
            }
        }

        foreach ($newKeys as $key => $item) {
            $meta = ($item instanceof MetaItemInterface ? $this->getMeta($item) : null);

            if (!array_key_exists($key, $oldKeys)) {
                // Add a new ItemPrice with a matching key (added)
                $newItem = $this->pricingFactory->metaItemPrice(0, 0, $key);

                // Update the existing item to indicate it is being added
                if ($meta) {
                    $item = $this->updateMeta($item, ['_data' => ['state' => 'added']]);
                }

                $oldCollection->append($newItem);
            } elseif ($meta) {
                // A pair already exists (updated)
                // Update the existing item to indicate it is being updated
                $item = $this->updateMeta($item, ['_data' => ['state' => 'updated']]);
            }
        }

        return compact('oldCollection', 'newCollection');
    }

    /**
     * Updates the collection to remove setup fees and cancel fees that shouldn't apply
     *
     * @param ItemPriceCollection $collection The collection
     * @return ItemPriceCollection The updated collection
     */
    private function removeFees(ItemPriceCollection $collection)
    {
        // Create a hash of all item keys
        $keys = $this->getKeys($collection);

        foreach ($collection as $item) {
            // Skip non-meta items
            if (!($item instanceof MetaItemInterface)) {
                continue;
            }

            $meta = $this->getMeta($item);

            // Remove this item from the collection if it is a cancel fee or if it is not a new setup fee
            if (isset($meta['_data']) && !empty($meta['_data']['type']) && !empty($meta['_data']['state'])) {
                $type = $meta['_data']['type'];
                $state = $meta['_data']['state'];

                // Remove the setup and cancel fees that are not being added. They do not apply
                if (in_array($type, ['setup', 'cancel']) && in_array($state, ['updated', 'removed'])) {
                    $collection->remove($item);
                    continue;
                }

                // Remove the cancel fees that are being added. They do not apply
                if ($type == 'cancel' && $state == 'added') {
                    $collection->remove($item);
                    continue;
                }

                // Remove setup fees that are not worth any amount
                if ($type == 'setup' && $item->price() <= 0) {
                    $collection->remove($item);
                    continue;
                }

                // Remove setup fees that are added for a config option that already exists
                // i.e. a new value has a setup fee while the old one doesn't
                // Expect the key to be of the form (...-option-#-setup) where '-setup' is the only difference
                // from the parent option
                if ($type == 'setup' && !empty($meta['_data']['item_type'])
                    && $meta['_data']['item_type'] == 'option'
                    && array_key_exists(str_replace('-setup', '', $item->key()), $keys)
                    && $keys[str_replace('-setup', '', $item->key())] != 'added'
                ) {
                    $collection->remove($item);
                }
            }
        }

        return $collection;
    }

    /**
     * Updates the collection to remove items that have not changed
     *
     * @param ItemPriceCollection $collection The collection
     * @return ItemPriceCollection The updated collection
     */
    private function removeUnchanged(ItemPriceCollection $collection)
    {
        foreach ($collection as $item) {
            // Skip non-meta items
            if (!($item instanceof MetaItemInterface)) {
                continue;
            }

            $meta = $this->getMeta($item);

            // Remove this item from the collection if it is an unchanged option
            if (isset($meta['_data']) && !empty($meta['_data']['type']) && !empty($meta['_data']['state'])) {
                $itemType = $meta['_data']['item_type'];
                $state = $meta['_data']['state'];

                // Only remove updated 'option' items whose price is zero
                if ($state == 'updated' && $itemType == 'option' && $item->price() == 0) {
                    $collection->remove($item);
                    continue;
                }
            }
        }

        return $collection;
    }

    /**
     * Hashes each collection's item keys in a key/value array
     *
     * @param ItemPriceCollection $collection
     * @return array An array of each item key (and its state for meta items)
     */
    private function getKeys(ItemPriceCollection $collection)
    {
        // Create a hash of all item keys
        $keys = [];
        foreach ($collection as $item) {
            // Set the state for meta items
            if (!($item instanceof MetaItemInterface)) {
                $keys[$item->key()] = $item->key();
            } else {
                $meta = $this->getMeta($item);
                $state = $meta['_data']['state'];
                $keys[$item->key()] = $state;
            }
        }

        return $keys;
    }

    /**
     * Creates a new Item which can be added as meta to an ItemPrice
     *
     * @param array|null $meta The meta data
     * @param string $state The state field to set on the meta data
     * @return Blesta\Items\Item\Item|null A new item with the given meta set, or null if no meta exists
     */
    private function makeMetaItem($meta, $state)
    {
        // Update the meta data and set it on a new item
        if ($meta && isset($meta['_data']) && is_array($meta['_data'])) {
            $meta['_data']['state'] = $state;

            // Set an item
            $metaItem = $this->itemFactory->item();
            $metaItem->setFields($meta);

            return $metaItem;
        }

        return null;
    }

    /**
     * Updates the given service data items to have the same keys as the service collection
     * for items that match, and which can be combined.
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $serviceCollection
     * @param Blesta\Pricing\Collection\ItemPriceCollection $serviceDataCollection
     * @return Blesta\Pricing\Collection\ItemPriceCollection The updated service data collection
     */
    private function combineKeys(ItemPriceCollection $serviceCollection, ItemPriceCollection $serviceDataCollection)
    {
        // Determine all services' assigned packages and discounts to use for comparison
        $servicePackages = [];
        $serviceOptions = [];

        foreach ($serviceCollection as $item) {
            // Combine keys only if there exists a key
            if (null === $item->key()) {
                continue;
            }

            $meta = $this->getMeta($item);

            // Create a hash of what service uses what package (there should only be 1)
            if (isset($meta['package']) && is_object($meta['package']) && isset($meta['package']->id)
                && isset($meta['service']) && is_object($meta['service']) && isset($meta['service']->id)
                && !array_key_exists($meta['service']->id, $servicePackages)
            ) {
                $servicePackages[$meta['service']->id] = [
                    'package_id' => $meta['package']->id,
                    'discounts' => $item->discounts()
                ];
            }

            // Create a hash of what option uses what service (there should only be 1 for each option)
            if (isset($meta['option']) && is_object($meta['option']) && isset($meta['option']->id)
                && isset($meta['option']->service_id)
            ) {
                if (!isset($serviceOptions[$meta['option']->service_id])) {
                    $serviceOptions[$meta['option']->service_id] = [];
                }

                $serviceOptions[$meta['option']->service_id][$meta['option']->id] = [
                    'discounts' => $item->discounts()
                ];
            }
        }

        // Update the service data item keys to match those from the service collection
        foreach ($serviceDataCollection as $item) {
            // Set the new combined item key
            if ($item instanceof MetaItemInterface) {
                $newKey = $this->getCombinedKey($item, $servicePackages, $serviceOptions);
                $item->setKey(($newKey !== null ? $newKey : $item->key()));
            }
        }

        return $serviceDataCollection;
    }

    /**
     * Retrieves the given $item's new key
     *
     * @param MetaItemInterface $item The item whose new key to retrieve
     * @param array $servicePackages An array keyed by service IDs that each contain an array:
     *
     *  - package_id The ID of the package
     *  - discounts An array of item discounts
     * @param array $serviceOptions An array keyed by service IDs that each contain an array of option IDs, which also
     *  contains an array:
     *
     *  - discounts An array of item discounts
     * @return string|null The new key
     */
    private function getCombinedKey(MetaItemInterface $item, array $servicePackages, array $serviceOptions)
    {
        // Combine keys only if there exists a key
        if (null === ($key = $item->key())) {
            return null;
        }

        $meta = $this->getMeta($item);
        if (isset($meta['option']) && isset($meta['option']->id)) {
            return $this->getOptionKey($item, $meta['option']->id, $serviceOptions);
        }

        return $this->getServiceKey($item, $servicePackages);
    }

    /**
     * Retrieves the new item key to use for the given service option item
     *
     * @param MetaItemInterface $item The item whose new key to retrieve
     * @param int $optionId The ID of the package option representing this item
     * @param array $serviceOptions An array keyed by service IDs that each contain an array of option IDs, which also
     *  contains an array:
     *
     *  - discounts An array of item discounts
     * @return string The item's new key
     */
    private function getOptionKey(MetaItemInterface $item, $optionId, array $serviceOptions)
    {
        $key = $item->key();
        $isOptionSetupFee = preg_match('/^package-[0-9]+-option-[0-9]+-setup/', $key);

        foreach ($serviceOptions as $serviceId => $optionIds) {
            // Skip invalid non-matching options
            if (!array_key_exists($optionId, $optionIds)) {
                continue;
            }

            // The discounts between the option data item and the option item must all match
            // otherwise the items cannot be combined into a single item because the discounts
            // would be applied to arrive at an incorrect price! The exception is setup fees since
            // we want to combine them so they can later be properly removed.
            if ($isOptionSetupFee
                || ($this->metaItemsMatch($optionIds[$optionId]['discounts'], $item->discounts())
                    && !$this->discountsContainType($item->discounts())
                    && !$this->discountsContainType($optionIds[$optionId]['discounts'])
                )
            ) {
                return preg_replace('/^package-([0-9]+)-option(-)?/', 'service-' . $serviceId . '-option${2}', $key);
            }
        }

        return $key;
    }

    /**
     * Retrieves the new item key to use for the given service item
     *
     * @param MetaItemInterface $item The item whose new key to retrieve
     * @param array $servicePackages An array keyed by service IDs that each contain an array:
     *
     *  - package_id The ID of the package
     *  - discounts An array of item discounts
     * @return string The item's new key
     */
    private function getServiceKey(MetaItemInterface $item, array $servicePackages)
    {
        $key = $item->key();
        $isPackageSetupFee = preg_match('/^package-([0-9]+)-setup$/', $key);

        foreach ($servicePackages as $serviceId => $data) {
            // Special case: The primary package setup fee applies if one exists and
            // if the service is not already using that package
            // (i.e. we're changing packages and incurring the new package's setup fee)
            if ($isPackageSetupFee && ($key != 'package-' . $data['package_id'] . '-setup')) {
                continue;
            }

            // The discounts between the service data item and the service item must all match
            // otherwise the items cannot be combined into a single item because the discounts
            // would be applied to arrive at an incorrect price! The exception is when dealing
            // with the setup fee.  Since the package has not been updated then the two items
            // should be combined so the setup fee is not reapplied.
            if ($isPackageSetupFee
                || ($this->metaItemsMatch($data['discounts'], $item->discounts())
                    && !$this->discountsContainType($data['discounts'])
                    && !$this->discountsContainType($item->discounts())
                )
            ) {
                return preg_replace('/^package-([0-9]+)(-)?/', 'service-' . $serviceId . '${2}', $key);
            }
        }

        return $key;
    }

    /**
     * Determines whether any of the given discounts contains a discount of the given type
     *
     * @param array $discounts An array of DiscountPrice objects
     * @param string $type The type of discount type to look for (optional, default 'amount')
     * @return bool True if any of the given discount prices are of the given type, or false otherwise
     */
    private function discountsContainType(array $discounts, $type = 'amount')
    {
        // Determine whether any discount in the list is of the given type
        foreach ($discounts as $discount) {
            if ($discount instanceof PriceModifierInterface && $discount->type() == $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compares all discounts from $discounts1 with $discounts2 to check whether all discounts are present identically
     * in both arguments
     *
     * @param array $item1 An array of discount objects to compare
     * @param array $item2 An array of discount objects to compare against
     * @return bool True if the arguments contain the same identical discounts, or false otherwise
     */
    private function metaItemsMatch(array $item1, array $item2)
    {
        // To be identical, both sets must have the same number of meta items
        if (count($item1) != count($item2)) {
            return false;
        }

        // Each meta item should also exist in both sets
        foreach ($item1 as $item) {
            // The item must be a MetaItem
            if (!($item instanceof MetaItemInterface)) {
                return false;
            }

            // The item object must be in the list
            if (!in_array($item, $item2)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates an instance of the ServiceBuilder
     *
     * @return Blesta\Core\Pricing\Presenter\Build\Service\ServiceBuilder
     */
    private function serviceBuilder()
    {
        // Create the builder factory to build the Service and Service Data
        $factory = $this->builderFactory();

        // Build the service and include the same settings/taxes/discounts/options, etc.
        $serviceBuilder = $factory->service();
        $serviceBuilder->settings($this->settings);
        $serviceBuilder->taxes($this->taxes);
        $serviceBuilder->discounts(isset($this->discounts['old']) ? $this->discounts['old'] : []);
        $serviceBuilder->options(array_merge($this->options, ['recur' => true]));

        return $serviceBuilder;
    }

    /**
     * Creates an instance of the ServiceDataBuilder
     *
     * @return Blesta\Core\Pricing\Presenter\Build\ServiceData\ServiceDataBuilder
     */
    private function serviceDataBuilder()
    {
        // Create the builder factory to build the Service and Service Data
        $factory = $this->builderFactory();

        // Change the prorate end date for the service data if provided
        $options = $this->options;
        if (!empty($options['prorateEndDateData'])) {
            $options['prorateEndDate'] = $options['prorateEndDateData'];
        }

        // Build service data and include the same settings/taxes/discounts/options, etc.
        $serviceDataBuilder = $factory->serviceData();
        $serviceDataBuilder->settings($this->settings);
        $serviceDataBuilder->taxes($this->taxes);
        $serviceDataBuilder->discounts(isset($this->discounts['new']) ? $this->discounts['new'] : []);
        $serviceDataBuilder->options($options);

        return $serviceDataBuilder;
    }

    /**
     * Creates an instance of the BuilderFactory
     *
     * @return Blesta\Core\Pricing\Presenter\Build\BuilderFactory
     */
    private function builderFactory()
    {
        return new BuilderFactory(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }
}
