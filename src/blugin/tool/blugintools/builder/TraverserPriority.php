<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\tool\blugintools\builder;

use pocketmine\event\EventPriority as Priority;

/**
 * List of traserver priorities extends EventPriority
 *
 * Traverser will be called in this order:
 * BEFORE_SPLIT -> (spliting file ->) LOWEST -> LOW -> NORMAL -> HIGH -> HIGHEST
 */
final class TraverserPriority{
    private function __construct(){
    }

    public const ALL = [
        self::VIRION_INFECT,
        self::BEFORE_SPLIT,
        Priority::LOWEST,
        Priority::LOW,
        Priority::NORMAL,
        Priority::HIGH,
        Priority::HIGHEST,
        Priority::MONITOR
    ];

    public const DEFAULTS = Priority::ALL;

    /**
     * Traserver call for Virion infection
     */
    public const VIRION_INFECT = 7;

    /**
     * Traserver call before split files
     */
    public const BEFORE_SPLIT = 6;

    public const LOWEST = Priority::LOWEST;
    public const LOW = Priority::LOW;
    public const NORMAL = Priority::NORMAL;
    public const HIGH = Priority::HIGH;
    public const HIGHEST = Priority::HIGHEST;
    public const MONITOR = Priority::MONITOR;
}
