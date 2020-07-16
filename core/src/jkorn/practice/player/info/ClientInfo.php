<?php

declare(strict_types=1);

namespace jkorn\practice\player\info;


class ClientInfo
{

    public const ANDROID = 1;
    public const IOS = 2;
    public const OSX = 3;
    public const FIREOS = 4; // Kindle Fire
    public const GEARVR = 5;
    public const HOLOLENSVR = 6;
    public const WINDOWS_10 = 7;
    public const WINDOWS_32 = 8; // Education Edition
    public const DEDICATED = 9;
    public const TVOS = 10;
    public const PS4 = 11;
    public const SWITCH = 12; // Nintendo Switch
    public const XBOX = 13;
    public const LINUX = 20;

    public const KEYBOARD = 1;
    public const TOUCH = 2;
    public const CONTROLLER = 3;
    public const MOTION_CONTROLLER = 4;

    /** @var array */
    private $clientData;

    public function __construct(array $clientData)
    {
        $deviceModel = (string)$clientData["DeviceModel"];
        $deviceOS = (string)$clientData["DeviceOS"];

        if(trim($deviceModel) === "") {

            switch($deviceOS) {
                case self::ANDROID:
                    $deviceModel = "Linux";
                    $deviceOS = self::LINUX;
                    break;
                case self::XBOX:
                    $deviceModel = "Xbox One";
                    break;
            }
        }

        $clientData["DeviceModel"] = $deviceModel;
        $clientData["DeviceOS"] = $deviceOS;

        $this->clientData = $clientData;
    }

    /**
     * @param bool $asString
     * @return string|int
     *
     * Gets the input as either a string or int.
     */
    public function getInput(bool $asString = false)
    {
        $input = (int)$this->clientData["CurrentInputMode"];
        if($asString)  {

            switch($input) {
                case self::KEYBOARD:
                    return "Keyboard";
                case self::TOUCH:
                    return "Touch";
                case self::CONTROLLER:
                    return "Controller";
                case self::MOTION_CONTROLLER:
                    return "Motion-Controller";
            }

            return "Unknown";
        }
        return $input;
    }

    /**
     * @param bool $asString
     * @return string|int
     *
     * Gets the device os as either a string or int.
     */
    public function getDeviceOS(bool $asString = false)
    {
        $os = (int)$this->clientData['DeviceOS'];

        if($asString) {

            switch($os) {
                case self::ANDROID:
                    return "Android";
                case self::IOS:
                    return "iOS";
                case self::OSX:
                    return "MacOS";
                case self::FIREOS:
                    return "FireOS";
                case self::GEARVR:
                    return "Gear-VR";
                case self::HOLOLENSVR:
                    return "Hololens-VR";
                case self::WINDOWS_10:
                    return "Win10";
                case self::WINDOWS_32:
                    return "Win32";
                case self::DEDICATED:
                    return "Dedicated";
                case self::TVOS:
                    return "TVOS";
                case self::PS4:
                    return "PS4";
                case self::SWITCH:
                    return "Nintendo Switch";
                case self::XBOX:
                    return "Xbox";
                case self::LINUX:
                    return "Linux";
                default:
                    return "Unknown";

            }
        }

        return $os;
    }

    /**
     * @return bool
     *
     * Determines if the player is a pocket edition player.
     */
    public function isPE(): bool
    {
        $deviceOS = $this->getDeviceOS();

        if($deviceOS === self::PS4 || $deviceOS === self::XBOX || $deviceOS === self::LINUX || $deviceOS === self::WINDOWS_10) {
            return false;
        }

        return $this->getInput() === self::TOUCH;
    }
}