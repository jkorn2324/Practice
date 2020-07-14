<?php

declare(strict_types=1);

namespace practice\data;

use practice\data\providers\EmptyDataProvider;

/**
 * Class PracticeDataManager.
 *
 * This handles how we are going to store the data for the server.
 *
 * @package practice\data
 */
class PracticeDataManager
{

    /** @var IDataProvider|null */
    private static $dataProvider = null;

    /**
     * @param IDataProvider $provider
     *
     * Sets the current data provider.
     */
    public static function setDataProvider(IDataProvider $provider): void
    {
        self::$dataProvider = $provider;
    }

    /**
     * @return IDataProvider
     *
     * Gets the data provider of the plugin.
     */
    public static function getDataProvider(): IDataProvider
    {
        if(self::$dataProvider === null)
        {
            self::$dataProvider = new EmptyDataProvider();
        }

        return self::$dataProvider;
    }
}