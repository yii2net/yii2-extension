<?php
namespace Openadm\Extension\Implement;

class Version
{
    const SHORT_COMMIT_LENGTH = 7;

    /** @var string */
    private $packageName;

    /** @var string */
    private $shortVersion;

    /** @var string */
    private $commitHash;

    /** @var bool */
    private $versionIsTagged;

    /**
     * @see composer packagist: ocramius/package-versions
     * @param string $packageName
     * @return Version
     */
    public static function getVersion(string $packageName): Version
    {
        return new Version($packageName, static::getPackageVersion($packageName));
    }

    public static function getPackageVersion($packageName)
    {
        $version = '';
        $oClass = new \ReflectionClass("\PackageVersions\Versions");
        $path = $oClass->getFileName();
        $content = file_get_contents($path);
        $packageName = str_replace('/','\/',$packageName);
        $exp = "/{$packageName}'[^,]*\'(.*)\'\s*,/i";
        if(preg_match($exp,$content,$m)){
            $version = $m[1];
        }else{
            throw new \OutOfBoundsException(
                'Required package "' . $packageName . '" is not installed: cannot detect its version'
            );
        }
        return $version;
    }


    /**
     * Version constructor.
     * @param string $packageName
     * @param string $version
     */
    public function __construct(string $packageName, string $version)
    {
        $this->packageName = $packageName;
        $splittedVersion = explode('@', $version);
        $this->shortVersion = $splittedVersion[0];
        $this->commitHash = $splittedVersion[1];
        $this->versionIsTagged = preg_match('/[^v\d\.]/', $this->getShortVersion()) === 0;
    }

    public function getPrettyVersion(): string
    {
        if ($this->versionIsTagged) {
            return $this->getShortVersion();
        }

        return $this->getVersionWithShortCommit();
    }

    public function getFullVersion(): string
    {
        return $this->getShortVersion() . '@' . $this->getCommitHash();
    }

    public function getVersionWithShortCommit(): string
    {
        return $this->getShortVersion() . '@' . $this->getShortCommitHash();
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getShortVersion(): string
    {
        return $this->shortVersion;
    }

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function getShortCommitHash(): string
    {
        return substr($this->commitHash, 0, self::SHORT_COMMIT_LENGTH);
    }

    public function __toString(): string
    {
        return $this->getPrettyVersion();
    }
}