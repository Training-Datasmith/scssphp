<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Extend;

use Scss_Php\Scss_Php\Ast\Css\Css_Media_Query;
use Scss_Php\Scss_Php\Ast\Sass\Statement\Extend_Rule;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Complex_Selector_Component;
use Scss_Php\Scss_Php\Ast\Selector\Compound_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Placeholder_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector;
use Scss_Php\Scss_Php\Ast\Selector\Selector_List;
use Scss_Php\Scss_Php\Ast\Selector\Simple_Selector;
use Scss_Php\Scss_Php\Exception\Sass_Exception;
use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Exception\Simple_Sass_Exception;
use Scss_Php\Scss_Php\Util;
use Scss_Php\Scss_Php\Util\Box;
use Scss_Php\Scss_Php\Util\Equatable_Util;
use Scss_Php\Scss_Php\Util\Iterable_Util;
use Scss_Php\Scss_Php\Util\List_Util;
use Scss_Php\Scss_Php\Util\Modifiable_Box;
use Source_Span\File_Span;
/**
 * @internal
 */
final class Concrete_Extension_Store implements Extension_Store
{
    /**
     * Extends $selector with $source extender and $targets extendees.
     *
     * This works as though `source {@extend target}` were written in the
     * stylesheet, with the exception that $target can contain compound
     * selectors which must be extended as a unit.
     */
    public static function extend(Selector_List $selector, Selector_List $source, Selector_List $targets, File_Span $span): Selector_List
    {
        return self::extend_or_replace($selector, $source, $targets, Extend_Mode::allTargets, $span);
    }
    /**
     * Returns a copy of $selector with $targets replaced by $source.
     */
    public static function replace(Selector_List $selector, Selector_List $source, Selector_List $targets, File_Span $span): Selector_List
    {
        return self::extend_or_replace($selector, $source, $targets, Extend_Mode::replace, $span);
    }
    /**
     * A helper function for {@see extend} and {@see replace}.
     */
    private static function extend_or_replace(Selector_List $selector, Selector_List $source, Selector_List $targets, Extend_Mode $mode, File_Span $span): Selector_List
    {
        $extender = Concrete_Extension_Store::create_for_mode($mode);
        if (!$selector->is_invisible()) {
            foreach ($selector->get_components() as $component) {
                $extender->originals->offsetSet($component);
            }
        }
        foreach ($targets->get_components() as $complex) {
            $compound = $complex->get_single_compound();
            if ($compound === null) {
                throw new Sass_Script_Exception("Can't extend complex selector {$complex}.");
            }
            $extensions = new Simple_Selector_Map();
            foreach ($compound->get_components() as $simple) {
                $extension_map = new Complex_Selector_Map();
                foreach ($source->get_components() as $source_complex) {
                    $extension_map[$source_complex] = new Extension($source_complex, $simple, $span, optional: true);
                }
                $extensions[$simple] = $extension_map;
            }
            $selector = $extender->extend_list($selector, $extensions);
        }
        return $selector;
    }
    /**
     * @param SimpleSelectorMap<ObjectSet<ModifiableBox<SelectorList>>> $selectors
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param SimpleSelectorMap<list<Extension>> $extensionsByExtender
     * @param \SplObjectStorage<ModifiableBox<SelectorList>, list<CssMediaQuery>> $mediaContexts
     * @param \SplObjectStorage<SimpleSelector, int> $sourceSpecificity
     * @param \SplObjectStorage<ComplexSelector, mixed> $originals
     */
    private function __construct(
        /**
         * A map from all simple selectors in the stylesheet to the selector lists
         * that contain them.
         *
         * This is used to find which selectors an `@extend` applies to and adjust
         * them.
         */
        private readonly Simple_Selector_Map $selectors,
        /**
         * A map from all extended simple selectors to the sources of those
         * extensions.
         */
        private Simple_Selector_Map $extensions,
        /**
         * A map from all simple selectors in extenders to the extensions that those
         * extenders define.
         */
        private Simple_Selector_Map $extensions_by_extender,
        /**
         * A map from CSS selectors to the media query contexts they're defined in.
         *
         * This tracks the contexts in which each selector's style rule is defined.
         * If a rule is defined at the top level, it doesn't have an entry.
         */
        private readonly \Spl_Object_Storage $media_contexts,
        private \Spl_Object_Storage $source_specificity,
        private readonly \Spl_Object_Storage $originals,
        private readonly Extend_Mode $mode
    )
    {
    }
    public static function create(): self
    {
        return self::create_for_mode(Extend_Mode::normal);
    }
    private static function create_for_mode(Extend_Mode $mode): self
    {
        /** @var \SplObjectStorage<ModifiableBox<SelectorList>, list<CssMediaQuery>> $mediaContexts */
        $media_contexts = new \Spl_Object_Storage();
        /** @var \SplObjectStorage<SimpleSelector, int> $sourceSpecificity */
        $source_specificity = new \Spl_Object_Storage();
        /** @var \SplObjectStorage<ComplexSelector, mixed> $originals */
        $originals = new \Spl_Object_Storage();
        return new self(new Simple_Selector_Map(), new Simple_Selector_Map(), new Simple_Selector_Map(), $media_contexts, $source_specificity, $originals, $mode);
    }
    public function is_empty(): bool
    {
        return \count($this->extensions) === 0;
    }
    public function get_simple_selectors(): array
    {
        return iterator_to_array($this->selectors);
    }
    public function extensions_where_target(callable $callback): iterable
    {
        foreach ($this->extensions as $simple) {
            if (!$callback($simple)) {
                continue;
            }
            $sources = $this->extensions[$simple];
            foreach ($sources->get_values() as $extension) {
                if ($extension instanceof Merged_Extension) {
                    foreach ($extension->unmerge() as $leaf_extension) {
                        if (!$leaf_extension->is_optional) {
                            yield $leaf_extension;
                        }
                    }
                } elseif (!$extension->is_optional) {
                    yield $extension;
                }
            }
        }
    }
    public function add_selector(Selector_List $selector, ?array $media_context): Box
    {
        $original_selector = $selector;
        if (!$original_selector->is_invisible()) {
            foreach ($original_selector->get_components() as $component) {
                $this->originals->offsetSet($component);
            }
        }
        if (\count($this->extensions) !== 0) {
            try {
                $selector = $this->extend_list($original_selector, $this->extensions, $media_context);
            } catch (Sass_Exception $e) {
                throw new Simple_Sass_Exception("From {$e->get_span()->message('')}\n" . $e->get_original_message(), $e->get_span(), $e);
            }
        }
        $modifiable_selector = new Modifiable_Box($selector);
        if ($media_context !== null) {
            $this->media_contexts->offsetSet($modifiable_selector, $media_context);
        }
        $this->register_selector($selector, $modifiable_selector);
        return $modifiable_selector->seal();
    }
    /**
     * Registers the {@see SimpleSelector}s in $list to point to $selector in
     * {@see selectors}.
     *
     * @param ModifiableBox<SelectorList> $selector
     */
    private function register_selector(Selector_List $list, Modifiable_Box $selector): void
    {
        foreach ($list->get_components() as $complex) {
            foreach ($complex->get_components() as $component) {
                foreach ($component->get_selector()->get_components() as $simple) {
                    if (!isset($this->selectors[$simple])) {
                        /** @var ObjectSet<ModifiableBox<SelectorList>> $set */
                        $set = new Object_Set();
                        $this->selectors->offsetSet($simple, $set);
                    }
                    $this->selectors[$simple]->add($selector);
                    if ($simple instanceof Pseudo_Selector && $simple->get_selector() !== null) {
                        $this->register_selector($simple->get_selector(), $selector);
                    }
                }
            }
        }
    }
    public function add_extension(Selector_List $extender, Simple_Selector $target, Extend_Rule $extend, ?array $media_context): void
    {
        $selectors = $this->selectors[$target] ?? null;
        $existing_extensions = $this->extensions_by_extender[$target] ?? null;
        $new_extensions = null;
        $sources = $this->extensions[$target] ??= new Complex_Selector_Map();
        foreach ($extender->get_components() as $complex) {
            if ($complex->is_useless()) {
                continue;
            }
            $extension = new Extension($complex, $target, $extend->get_span(), $media_context, $extend->is_optional());
            $existing_extension = $sources[$complex] ?? null;
            if ($existing_extension !== null) {
                // If there's already an extend from $extender to $target, we don't need
                // to re-run the extension. We may need to mark the extension as
                // mandatory, though.
                $sources[$complex] = Merged_Extension::merge($existing_extension, $extension);
                continue;
            }
            $sources[$complex] = $extension;
            foreach ($this->simple_selectors($complex) as $simple) {
                $extensions_by_extender = $this->extensions_by_extender[$simple] ?? [];
                $extensions_by_extender[] = $extension;
                $this->extensions_by_extender[$simple] = $extensions_by_extender;
                // Only source specificity for the original selector is relevant.
                // Selectors generated by `@extend` don't get new specificity.
                $this->source_specificity[$simple] ??= $complex->get_specificity();
            }
            if ($selectors !== null || $existing_extensions !== null) {
                /** @var ComplexSelectorMap<Extension> $newExtensions */
                $new_extensions ??= new Complex_Selector_Map();
                $new_extensions[$complex] = $extension;
            }
        }
        if ($new_extensions === null) {
            return;
        }
        /** @var SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensionsByTarget */
        $new_extensions_by_target = new Simple_Selector_Map();
        $new_extensions_by_target[$target] = $new_extensions;
        if ($existing_extensions !== null) {
            // Reload the list of existing extensions as it is an array, not an object.
            $existing_extensions = $this->extensions_by_extender[$target];
            $additional_extensions = $this->extend_existing_extensions($existing_extensions, $new_extensions_by_target);
            if ($additional_extensions !== null) {
                Util::map_add_all2($new_extensions_by_target, $additional_extensions);
            }
        }
        if ($selectors !== null) {
            $this->extend_existing_selectors($selectors, $new_extensions_by_target);
        }
    }
    /**
     * Returns an iterable of all simple selectors in $complex.
     *
     * @return iterable<SimpleSelector>
     */
    private function simple_selectors(Complex_Selector $complex): iterable
    {
        foreach ($complex->get_components() as $component) {
            foreach ($component->get_selector()->get_components() as $simple) {
                yield $simple;
                if ($simple instanceof Pseudo_Selector && $simple->get_selector() !== null) {
                    foreach ($simple->get_selector()->get_components() as $pseudo_complex) {
                        yield from $this->simple_selectors($pseudo_complex);
                    }
                }
            }
        }
    }
    /**
     * Extend $extensions using $newExtensions.
     *
     * Note that this does duplicate some work done by
     * {@see extendExistingSelectors}, but it's necessary to expand each extension's
     * extender separately without reference to the full selector list, so that
     * relevant results don't get trimmed too early.
     *
     * Returns extensions that should be added to $newExtensions before
     * extending selectors in order to properly handle extension loops such as:
     *
     *     .c {x: y; @extend .a}
     *     .x.y.a {@extend .b}
     *     .z.b {@extend .c}
     *
     * Returns `null` if there are no extensions to add.
     *
     * @param list<Extension> $extensions
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensions
     * @return SimpleSelectorMap<ComplexSelectorMap<Extension>>|null
     */
    private function extend_existing_extensions(array $extensions, Simple_Selector_Map $new_extensions): ?Simple_Selector_Map
    {
        $additional_extensions = null;
        foreach ($extensions as $extension) {
            $sources = $this->extensions[$extension->target];
            try {
                $selectors = $this->extend_complex($extension->extender->selector, $new_extensions, $extension->media_context);
                if ($selectors === null) {
                    continue;
                }
            } catch (Sass_Exception $e) {
                throw $e->with_additional_span($extension->extender->selector->get_span(), 'target selector', $e);
            }
            // If the output contains the original complex selector, there's no need
            // to recreate it.
            $contains_extension = Equatable_Util::equals($selectors[0], $extension->extender->selector);
            if ($contains_extension) {
                $selectors = array_slice($selectors, 1);
            }
            foreach ($selectors as $complex) {
                $with_extender = $extension->with_extender($complex);
                $existing_extension = $sources[$complex] ?? null;
                if ($existing_extension !== null) {
                    $sources[$complex] = Merged_Extension::merge($existing_extension, $with_extender);
                } else {
                    $sources[$complex] = $with_extender;
                    foreach ($complex->get_components() as $component) {
                        foreach ($component->get_selector()->get_components() as $simple) {
                            $extensions_by_extender = $this->extensions_by_extender[$simple] ?? [];
                            $extensions_by_extender[] = $with_extender;
                            $this->extensions_by_extender[$simple] = $extensions_by_extender;
                        }
                    }
                    if ($new_extensions->offsetExists($extension->target)) {
                        /** @var SimpleSelectorMap<ComplexSelectorMap<Extension>> $additionalExtensions */
                        $additional_extensions ??= new Simple_Selector_Map();
                        if (!isset($additional_extensions[$extension->target])) {
                            /** @var ComplexSelectorMap<Extension> $additionalSources */
                            $additional_sources = new Complex_Selector_Map();
                            $additional_extensions[$extension->target] = $additional_sources;
                        } else {
                            $additional_sources = $additional_extensions[$extension->target];
                        }
                        $additional_sources[$complex] = $with_extender;
                    }
                }
            }
        }
        return $additional_extensions;
    }
    /**
     * Extend $selectors using $newExtensions.
     *
     * @param ObjectSet<ModifiableBox<SelectorList>> $selectors
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensions
     */
    private function extend_existing_selectors(Object_Set $selectors, Simple_Selector_Map $new_extensions): void
    {
        foreach ($selectors as $selector) {
            $old_value = $selector->get_value();
            try {
                $selector->set_value($this->extend_list($selector->get_value(), $new_extensions, $this->media_contexts[$selector] ?? null));
            } catch (Sass_Exception $e) {
                throw new Simple_Sass_Exception("From {$old_value->get_span()->message('')}\n" . $e->get_original_message(), $e->get_span(), $e);
            }
            // If no extends actually happened (for example because unification
            // failed), we don't need to re-register the selector.
            if ($old_value === $selector->get_value()) {
                continue;
            }
            $this->register_selector($selector->get_value(), $selector);
        }
    }
    /**
     * @param iterable<ExtensionStore> $extensionStores
     */
    public function add_extensions(iterable $extension_stores): void
    {
        /** @var list<Extension>|null $extensionsToExtend */
        $extensions_to_extend = null;
        $selectors_to_extend = null;
        $new_extensions = null;
        foreach ($extension_stores as $extension_store) {
            if ($extension_store->is_empty()) {
                continue;
            }
            \assert($extension_store instanceof Concrete_Extension_Store);
            $this->source_specificity->add_all($extension_store->source_specificity);
            foreach ($extension_store->extensions as $target) {
                $new_sources = $extension_store->extensions->get_info();
                // Private selectors can't be extended across module boundaries.
                if ($target instanceof Placeholder_Selector && $target->is_private()) {
                    continue;
                }
                $extensions_for_target = $this->extensions_by_extender[$target] ?? null;
                if ($extensions_for_target !== null) {
                    $extensions_to_extend ??= [];
                    array_push($extensions_to_extend, ...$extensions_for_target);
                }
                // Find existing selectors to extend.
                $selectors_for_target = $this->selectors[$target] ?? null;
                if ($selectors_for_target !== null) {
                    if ($selectors_to_extend === null) {
                        /** @var ObjectSet<ModifiableBox<SelectorList>> $selectorsToExtend */
                        $selectors_to_extend = new Object_Set();
                    }
                    $selectors_to_extend->add_all($selectors_for_target);
                }
                $existing_sources = $this->extensions[$target] ?? null;
                if ($existing_sources !== null) {
                    foreach ($new_sources as $extender) {
                        $extension = $new_sources->get_info();
                        if (isset($existing_sources[$extender])) {
                            $extension = Merged_Extension::merge($existing_sources[$extender], $extension);
                        }
                        $existing_sources[$extender] = $extension;
                        if ($extensions_for_target !== null || $selectors_for_target !== null) {
                            /** @var SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensions */
                            $new_extensions ??= new Simple_Selector_Map();
                            if (!isset($new_extensions[$target])) {
                                /** @var ComplexSelectorMap<Extension> $newMap */
                                $new_map = new Complex_Selector_Map();
                                $new_extensions[$target] = $new_map;
                            }
                            $new_extensions[$target][$extender] = $extension;
                        }
                    }
                } else {
                    $this->extensions[$target] = clone $new_sources;
                    if ($extensions_for_target !== null || $selectors_for_target !== null) {
                        /** @var SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensions */
                        $new_extensions ??= new Simple_Selector_Map();
                        $new_extensions[$target] = clone $new_sources;
                    }
                }
            }
        }
        if ($new_extensions !== null) {
            // We can ignore the return value here because it's only useful for extend
            // loops, which can't exist across module boundaries.
            if ($extensions_to_extend !== null) {
                $this->extend_existing_extensions($extensions_to_extend, $new_extensions);
            }
            if ($selectors_to_extend !== null) {
                $this->extend_existing_selectors($selectors_to_extend, $new_extensions);
            }
        }
    }
    /**
     * Extends $list using $extensions.
     *
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param list<CssMediaQuery>|null $mediaQueryContext
     */
    private function extend_list(Selector_List $list, Simple_Selector_Map $extensions, ?array $media_query_context = null): Selector_List
    {
        $extended = null;
        foreach ($list->get_components() as $i => $complex) {
            $result = $this->extend_complex($complex, $extensions, $media_query_context);
            \assert($result === null || \count($result) > 0, "extendComplex({$complex}) should return null rather than [] if extension fails.");
            if ($result === null) {
                if ($extended !== null) {
                    $extended[] = $complex;
                }
            } else {
                $extended ??= $i === 0 ? [] : array_slice($list->get_components(), 0, $i);
                array_push($extended, ...$result);
            }
        }
        if ($extended === null) {
            return $list;
        }
        return new Selector_List($this->trim($extended, $this->originals->offsetExists(...)), $list->get_span());
    }
    /**
     * Extends $complex using $extensions, and returns the contents of a
     * {@see SelectorList}.
     *
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param list<CssMediaQuery>|null $mediaQueryContext
     * @return list<ComplexSelector>|null
     */
    private function extend_complex(Complex_Selector $complex, Simple_Selector_Map $extensions, ?array $media_query_context): ?array
    {
        if (\count($complex->get_leading_combinators()) > 1) {
            return null;
        }
        // The complex selectors that each compound selector in $complex->getComponents()
        // can expand to.
        //
        // For example, given
        //
        //     .a .b {...}
        //     .x .y {@extend .b}
        //
        // this will contain
        //
        //     [
        //       [.a],
        //       [.b, .x .y]
        //     ]
        //
        $extended_not_expanded = null;
        $is_original = $this->originals->offsetExists($complex);
        foreach ($complex->get_components() as $i => $component) {
            $extended = $this->extend_compound($component, $extensions, $media_query_context, $is_original);
            \assert($extended === null || \count($extended) > 0, "extendCompound({$component}) should return null rather than [] if extension fails.");
            if ($extended === null) {
                if ($extended_not_expanded !== null) {
                    $extended_not_expanded[] = [new Complex_Selector([], [$component], $complex->get_span(), $complex->get_line_break())];
                }
            } elseif ($extended_not_expanded !== null) {
                $extended_not_expanded[] = $extended;
            } elseif ($i !== 0) {
                $extended_not_expanded = [[new Complex_Selector($complex->get_leading_combinators(), array_slice($complex->get_components(), 0, $i), $complex->get_span(), $complex->get_line_break())], $extended];
            } elseif (\count($complex->get_leading_combinators()) === 0) {
                $extended_not_expanded = [$extended];
            } else {
                $new_extended = [];
                foreach ($extended as $new_complex) {
                    if (\count($new_complex->get_leading_combinators()) === 0 || Equatable_Util::list_equals($complex->get_leading_combinators(), $new_complex->get_leading_combinators())) {
                        $new_extended[] = new Complex_Selector($complex->get_leading_combinators(), $new_complex->get_components(), $complex->get_span(), $complex->get_line_break() || $new_complex->get_line_break());
                    }
                }
                $extended_not_expanded = [$new_extended];
            }
        }
        if ($extended_not_expanded === null) {
            return null;
        }
        $first = true;
        return iterator_to_array(self::expand_iterable(Extend_Util::paths($extended_not_expanded), function (array $path) use (&$first, $complex): array {
            return array_map(function (Complex_Selector $output_complex) use (&$first, $complex): \Scss_Php\Scss_Php\Ast\Selector\Complex_Selector {
                // Make sure that copies of $complex retain their status as "original"
                // selectors. This includes selectors that are modified because a :not()
                // was extended into.
                if ($first && $this->originals->offsetExists($complex)) {
                    $this->originals->offsetSet($output_complex);
                }
                $first = false;
                return $output_complex;
            }, Extend_Util::weave($path, $complex->get_span(), $complex->get_line_break()));
        }), false);
    }
    /**
     * Extends $component using $extensions, and returns the contents of a
     * {@see SelectorList}.
     *
     * The $inOriginal parameter indicates whether this is in an original
     * complex selector, meaning that the compound should not be trimmed out.
     *
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param list<CssMediaQuery>|null $mediaQueryContext
     * @return list<ComplexSelector>|null
     */
    private function extend_compound(Complex_Selector_Component $component, Simple_Selector_Map $extensions, ?array $media_query_context, bool $in_original): ?array
    {
        // If there's more than one target and they all need to match, we track
        // which targets are actually extended.
        $targets_used = $this->mode === Extend_Mode::normal || \count($extensions) < 2 ? null : new Simple_Selector_Map();
        $simples = $component->get_selector()->get_components();
        // The complex selectors produced from each simple selector in the compound selector.
        $options = null;
        foreach ($simples as $i => $simple) {
            $extended = $this->extend_simple($simple, $extensions, $media_query_context, $targets_used);
            \assert($extended === null || \count($extended) > 0, "extendSimple({$simple}) should return null rather than [] if extension fails.");
            if ($extended === null) {
                if ($options !== null) {
                    $options[] = [$this->extender_for_simple($simple)];
                }
            } else {
                if ($options === null) {
                    $options = [];
                    if ($i !== 0) {
                        $options[] = [$this->extender_for_compound(array_slice($simples, 0, $i), $component->get_span())];
                    }
                }
                array_push($options, ...$extended);
            }
        }
        if ($options === null) {
            return null;
        }
        /**
         * If {@see mode} isn't {@see ExtendMode::normal} and we didn't use all the targets in
         * $extensions, extension fails for $component.
         */
        if ($targets_used !== null && \count($targets_used) !== \count($extensions)) {
            return null;
        }
        // Optimize for the simple case of a single simple selector that doesn't
        // need any unification.
        if (\count($options) === 1) {
            $extenders = $options[0];
            $result = null;
            foreach ($extenders as $extender) {
                $extender->assert_compatible_media_context($media_query_context);
                $complex = $extender->selector->with_additional_combinators($component->get_combinators());
                if ($complex->is_useless()) {
                    continue;
                }
                $result ??= [];
                $result[] = $complex;
            }
            return $result;
        }
        // Find all paths through $options. In this case, each path represents a
        // different unification of the base selector. For example, if we have:
        //
        //     .a.b {...}
        //     .w .x {@extend .a}
        //     .y .z {@extend .b}
        //
        // then $options is `[[.a, .w .x], [.b, .y .z]]` and `paths($options)` is
        //
        //     [
        //       [.a, .b],
        //       [.a, .y .z],
        //       [.w .x, .b],
        //       [.w .x, .y .z]
        //     ]
        //
        // We then unify each path to get a list of complex selectors:
        //
        //     [
        //       [.a.b],
        //       [.y .a.z],
        //       [.w .x.b],
        //       [.w .y .x.z, .y .w .x.z]
        //     ]
        //
        // And finally flatten them to get:
        //
        //     [
        //       .a.b,
        //       .y .a.z,
        //       .w .x.b,
        //       .w .y .x.z,
        //       .y .w .x.z
        //     ]
        $extender_paths = Extend_Util::paths($options);
        $result = [];
        if ($this->mode !== Extend_Mode::replace) {
            // The first path is always the original selector. We can't just return
            // $component directly because selector pseudos may be modified, but we
            // don't have to do any unification.
            $result[] = new Complex_Selector([], [new Complex_Selector_Component(new Compound_Selector(iterator_to_array(self::expand_iterable($extender_paths[0], function (Extender $extender) {
                \assert(\count($extender->selector->get_components()) === 1);
                return List_Util::last($extender->selector->get_components())->get_selector()->get_components();
            }), false), $component->get_selector()->get_span()), $component->get_combinators(), $component->get_span())], $component->get_span());
        }
        foreach (array_slice($extender_paths, $this->mode === Extend_Mode::replace ? 0 : 1) as $path) {
            $extended = $this->unify_extenders($path, $media_query_context, $component->get_span());
            if ($extended === null) {
                continue;
            }
            foreach ($extended as $complex) {
                $with_combinators = $complex->with_additional_combinators($component->get_combinators());
                if (!$with_combinators->is_useless()) {
                    $result[] = $with_combinators;
                }
            }
        }
        // If we're preserving the original selector, mark the first unification as
        // such so {@see trim} doesn't get rid of it.
        $is_original = fn(Complex_Selector $complex): bool => false;
        if ($in_original && $this->mode !== Extend_Mode::replace) {
            $original = $result[0];
            $is_original = fn(Complex_Selector $complex): bool => Equatable_Util::equals($complex, $original);
        }
        return $this->trim($result, $is_original);
    }
    /**
     * Returns a list of {@see ComplexSelector}s that match the intersection of
     * elements matched by all of $extenders' selectors.
     *
     * The $span will be used for the new selectors.
     *
     * @param list<Extender> $extenders
     * @param list<CssMediaQuery>|null $mediaQueryContext
     * @return list<ComplexSelector>|null
     */
    private function unify_extenders(array $extenders, ?array $media_query_context, File_Span $span): ?array
    {
        $to_unify = [];
        $originals = null;
        $originals_line_break = false;
        foreach ($extenders as $extender) {
            if ($extender->is_original) {
                $originals ??= [];
                $final_extender_component = List_Util::last($extender->selector->get_components());
                \assert(\count($final_extender_component->get_combinators()) === 0);
                foreach ($final_extender_component->get_selector()->get_components() as $component) {
                    $originals[] = $component;
                }
                $originals_line_break = $originals_line_break || $extender->selector->get_line_break();
            } elseif ($extender->selector->is_useless()) {
                return null;
            } else {
                $to_unify[] = $extender->selector;
            }
        }
        if ($originals !== null) {
            array_unshift($to_unify, new Complex_Selector([], [new Complex_Selector_Component(new Compound_Selector($originals, $span), [], $span)], $span, $originals_line_break));
        }
        $complexes = Extend_Util::unify_complex($to_unify, $span);
        if ($complexes === null) {
            return null;
        }
        foreach ($extenders as $extender) {
            $extender->assert_compatible_media_context($media_query_context);
        }
        return $complexes;
    }
    /**
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param list<CssMediaQuery>|null $mediaQueryContext
     * @param SimpleSelectorMap<mixed>|null $targetsUsed
     * @return list<list<Extender>>|null
     */
    private function extend_simple(Simple_Selector $simple, Simple_Selector_Map $extensions, ?array $media_query_context, ?Simple_Selector_Map $targets_used): ?array
    {
        // Extends $simple without extending the contents of any selector pseudos
        // it contains.
        $without_pseudo = function (Simple_Selector $simple) use ($extensions, $targets_used): ?array {
            $extensions_for_simple = $extensions[$simple] ?? null;
            if ($extensions_for_simple === null) {
                return null;
            }
            $targets_used?->offsetSet($simple);
            $result = [];
            if ($this->mode !== Extend_Mode::replace) {
                $result[] = $this->extender_for_simple($simple);
            }
            /** @var Extension $extension */
            foreach ($extensions_for_simple->get_values() as $extension) {
                $result[] = $extension->extender;
            }
            return $result;
        };
        if ($simple instanceof Pseudo_Selector && $simple->get_selector() !== null) {
            $extended = $this->extend_pseudo($simple, $extensions, $media_query_context);
            if ($extended !== null) {
                return array_map(fn(\Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector $pseudo): array => $without_pseudo($pseudo) ?? [$this->extender_for_simple($pseudo)], $extended);
            }
        }
        $result = $without_pseudo($simple);
        if ($result === null) {
            return null;
        }
        return [$result];
    }
    /**
     * Returns an {@see Extender} composed solely of a compound selector containing
     * $simples.
     *
     * @param list<SimpleSelector> $simples
     */
    private function extender_for_compound(array $simples, File_Span $span): Extender
    {
        $compound = new Compound_Selector($simples, $span);
        return Extender::create(new Complex_Selector([], [new Complex_Selector_Component($compound, [], $span)], $span), $this->source_specificity_for($compound), true);
    }
    /**
     * Returns an {@see Extender} composed solely of $simple.
     */
    private function extender_for_simple(Simple_Selector $simple): Extender
    {
        return Extender::create(new Complex_Selector([], [new Complex_Selector_Component(new Compound_Selector([$simple], $simple->get_span()), [], $simple->get_span())], $simple->get_span()), $this->source_specificity[$simple] ?? 0, true);
    }
    /**
     * Extends $pseudo using $extensions, and returns a list of resulting
     * pseudo selectors.
     *
     * This requires that $pseudo have a selector argument.
     *
     * @param SimpleSelectorMap<ComplexSelectorMap<Extension>> $extensions
     * @param list<CssMediaQuery>|null                         $mediaQueryContext
     * @return list<PseudoSelector>|null
     */
    private function extend_pseudo(Pseudo_Selector $pseudo, Simple_Selector_Map $extensions, ?array $media_query_context): ?array
    {
        $selector = $pseudo->get_selector();
        if ($selector === null) {
            throw new \InvalidArgumentException("Selector {$pseudo} must have a selector argument.");
        }
        $extended = $this->extend_list($selector, $extensions, $media_query_context);
        if ($extended === $selector) {
            return null;
        }
        // For `:not()`, we usually want to get rid of any complex selectors because
        // that will cause the selector to fail to parse on all browsers at time of
        // writing. We can keep them if either the original selector had a complex
        // selector, or the result of extending has only complex selectors, because
        // either way we aren't breaking anything that isn't already broken.
        $complexes = $extended->get_components();
        if ($pseudo->get_normalized_name() === 'not' && !Iterable_Util::any($selector->get_components(), fn($complex): bool => \count($complex->get_components()) > 1) && Iterable_Util::any($extended->get_components(), fn($complex): bool => \count($complex->get_components()) === 1)) {
            $complexes = array_filter($extended->get_components(), fn($complex): bool => \count($complex->get_components()) <= 1);
        }
        $complexes = iterator_to_array(self::expand_iterable($complexes, function (Complex_Selector $complex) use ($pseudo): array {
            $inner_pseudo = $complex->get_single_compound()?->get_single_simple();
            if (!$inner_pseudo instanceof Pseudo_Selector) {
                return [$complex];
            }
            $inner_selector = $inner_pseudo->get_selector();
            if ($inner_selector === null) {
                return [$complex];
            }
            switch ($pseudo->get_normalized_name()) {
                case 'not':
                    // In theory, if there's a `:not` nested within another `:not`, the
                    // inner `:not`'s contents should be unified with the return value.
                    // For example, if `:not(.foo)` extends `.bar`, `:not(.bar)` should
                    // become `.foo:not(.bar)`. However, this is a narrow edge case and
                    // supporting it properly would make this code and the code calling it
                    // a lot more complicated, so it's not supported for now.
                    if (!\in_array($inner_pseudo->get_normalized_name(), ['is', 'matches', 'where'], true)) {
                        return [];
                    }
                    return $inner_selector->get_components();
                case 'is':
                case 'matches':
                case 'where':
                case 'any':
                case 'current':
                case 'nth-child':
                case 'nth-last-child':
                    // As above, we could theoretically support :not within :matches, but
                    // doing so would require this method and its callers to handle much
                    // more complex cases that likely aren't worth the pain.
                    if ($inner_pseudo->get_name() !== $pseudo->get_name()) {
                        return [];
                    }
                    if ($inner_pseudo->get_argument() !== $pseudo->get_argument()) {
                        return [];
                    }
                    return $inner_selector->get_components();
                case 'has':
                case 'host':
                case 'host-context':
                case 'slotted':
                    // We can't expand nested selectors here, because each layer adds an
                    // additional layer of semantics. For example, `:has(:has(img))`
                    // doesn't match `<div><img></div>` but `:has(img)` does.
                    return [$complex];
                default:
                    return [];
            }
        }), false);
        // Older browsers support `:not`, but only with a single complex selector.
        // In order to support those browsers, we break up the contents of a `:not`
        // unless it originally contained a selector list.
        if ($pseudo->get_normalized_name() === 'not' && \count($selector->get_components()) === 1) {
            $result = array_map(fn(Complex_Selector $complex): \Scss_Php\Scss_Php\Ast\Selector\Pseudo_Selector => $pseudo->with_selector(new Selector_List([$complex], $selector->get_span())), $complexes);
            return \count($result) === 0 ? null : $result;
        }
        return [$pseudo->with_selector(new Selector_List($complexes, $selector->get_span()))];
    }
    /**
     * @template E
     * @template T
     * @param iterable<E> $elements
     * @param callable(E): iterable<T> $callback
     * @return \Traversable<T>
     *
     * @param-immediately-invoked-callable $callback
     */
    private static function expand_iterable(iterable $elements, callable $callback): \Traversable
    {
        foreach ($elements as $element) {
            yield from $callback($element);
        }
    }
    /**
     * Removes elements from $selectors if they're subselectors of other
     * elements.
     *
     * The $isOriginal callback indicates which selectors are original to the
     * document, and thus should never be trimmed.
     *
     * @param list<ComplexSelector> $selectors
     * @param callable(ComplexSelector): bool $isOriginal
     * @return list<ComplexSelector>
     *
     * @param-immediately-invoked-callable $isOriginal
     */
    private function trim(array $selectors, callable $is_original): array
    {
        // Avoid truly horrific quadratic behavior.
        if (\count($selectors) > 100) {
            return $selectors;
        }
        // This is n² on the sequences, but only comparing between separate
        // sequences should limit the quadratic behavior. We iterate from last to
        // first and reverse the result so that, if two selectors are identical, we
        // keep the first one.
        /** @var list<ComplexSelector> $result */
        $result = [];
        $num_originals = 0;
        for ($i = \count($selectors) - 1; $i >= 0; $i--) {
            $complex1 = $selectors[$i];
            if ($is_original($complex1)) {
                // Make sure we don't include duplicate originals, which could happen if
                // a style rule extends a component of its own selector.
                for ($j = 0; $j < $num_originals; $j++) {
                    if (Equatable_Util::equals($result[$j], $complex1)) {
                        // Rotates the slice one index higher
                        $element = $result[$j];
                        for ($k = 0; $k <= $j; $k++) {
                            $next = $result[$k];
                            $result[$k] = $element;
                            $element = $next;
                        }
                        // Rotating the slice preserves the list status of the array, but phpstan does not recognize it.
                        \assert(array_is_list($result));
                        continue 2;
                    }
                }
                $num_originals++;
                array_unshift($result, $complex1);
                continue;
            }
            // The maximum specificity of the sources that caused $complex1 to be
            // generated. In order for $complex1 to be removed, there must be another
            // selector that's a superselector of it *and* that has specificity
            // greater or equal to this.
            $max_specificity = 0;
            foreach ($complex1->get_components() as $component) {
                $max_specificity = max($max_specificity, $this->source_specificity_for($component->get_selector()));
            }
            // Look in $result rather than $selectors for selectors after $i. This
            // ensures that we aren't comparing against a selector that's already been
            // trimmed, and thus that if there are two identical selectors only one is
            // trimmed.
            if (Iterable_Util::any($result, fn(Complex_Selector $complex2): bool => $complex2->get_specificity() >= $max_specificity && $complex2->is_superselector($complex1))) {
                continue;
            }
            if (Iterable_Util::any(array_slice($selectors, 0, $i), fn(Complex_Selector $complex2): bool => $complex2->get_specificity() >= $max_specificity && $complex2->is_superselector($complex1))) {
                continue;
            }
            array_unshift($result, $complex1);
        }
        return $result;
    }
    /**
     * Returns the maximum specificity for sources that went into producing
     * $compound.
     */
    private function source_specificity_for(Compound_Selector $compound): int
    {
        $specificity = 0;
        foreach ($compound->get_components() as $simple) {
            $specificity = max($specificity, $this->source_specificity[$simple] ?? 0);
        }
        return $specificity;
    }
    public function clone(): array
    {
        /** @var SimpleSelectorMap<ObjectSet<ModifiableBox<SelectorList>>> $newSelectors */
        $new_selectors = new Simple_Selector_Map();
        /** @var \SplObjectStorage<ModifiableBox<SelectorList>, list<CssMediaQuery>> $newMediaContexts */
        $new_media_contexts = new \Spl_Object_Storage();
        /** @var \SplObjectStorage<SelectorList, Box<SelectorList>> $oldToNewSelectors */
        $old_to_new_selectors = new \Spl_Object_Storage();
        foreach ($this->selectors as $simple) {
            $selectors = $this->selectors->get_info();
            /** @var ObjectSet<ModifiableBox<SelectorList>> $newSelectorSet */
            $new_selector_set = new Object_Set();
            $new_selectors[$simple] = $new_selector_set;
            foreach ($selectors as $selector) {
                $new_selector = new Modifiable_Box($selector->get_value());
                $new_selector_set->add($new_selector);
                $old_to_new_selectors[$selector->get_value()] = $new_selector->seal();
                if (isset($this->media_contexts[$selector])) {
                    $new_media_contexts[$new_selector] = $this->media_contexts[$selector];
                }
            }
        }
        /** @var SimpleSelectorMap<ComplexSelectorMap<Extension>> $newExtensions */
        $new_extensions = new Simple_Selector_Map();
        foreach ($this->extensions as $simple) {
            $new_extensions[$simple] = clone $this->extensions->get_info();
        }
        return [new Concrete_Extension_Store($new_selectors, $new_extensions, clone $this->extensions_by_extender, $new_media_contexts, clone $this->source_specificity, clone $this->originals, Extend_Mode::normal), $old_to_new_selectors];
    }
}