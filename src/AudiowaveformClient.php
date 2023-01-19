<?php
/**
 * User: Fabio Bacigalupo
 * Date: 13.11.22
 * Time: 21:46
 */

namespace podcasthosting\AudiowaveformClient;

require 'vendor/autoload.php';

use TitasGailius\Terminal\Terminal;

/**
# audiowaveform --help
AudioWaveform v1.6.0

Usage:
  audiowaveform [options]

Options:
  --help                          show help message
-v [ --version ]                show version information
-q [ --quiet ]                  disable progress and information messages
-i [ --input-filename ] arg     input file name (.mp3, .wav, .flac, .ogg,
                                  .oga, .opus, .dat)
  -o [ --output-filename ] arg    output file name (.wav, .dat, .png, .json)
  --split-channels                output multi-channel waveform data or image
                                  files
                                  --input-format arg              input file format (mp3, wav, flac, ogg, opus,
    dat)
--output-format arg             output file format (wav, dat, png, json)
-z [ --zoom ] arg (=256)        zoom level (samples per pixel)
  --pixels-per-second arg (=100)  zoom level (pixels per second)
  -b [ --bits ] arg (=16)         bits (8 or 16)
-s [ --start ] arg (=0)         start time (seconds)
-e [ --end ] arg (=0)           end time (seconds)
-w [ --width ] arg (=800)       image width (pixels)
-h [ --height ] arg (=250)      image height (pixels)
-c [ --colors ] arg (=audacity) color scheme (audition or audacity)
--border-color arg              border color (rrggbb[aa])
--background-color arg          background color (rrggbb[aa])
--waveform-color arg            wave color (rrggbb[aa])
--axis-label-color arg          axis label color (rrggbb[aa])
--no-axis-labels                render waveform image without axis labels
--with-axis-labels              render waveform image with axis labels
(default)
  --amplitude-scale arg (=1.0)    amplitude scale
--compression arg (=-1)         PNG compression level: 0 (none) to 9 (best),
                                  or -1 (default)
**/
class AudiowaveformClient
{
    const BINARY_NAME = 'audiowaveform';

    const DEFAULT_PATH = '/usr/bin';

    /**
     * @var string
     */
    private string $binaryName = self::BINARY_NAME;

    /**
     * @var string
     */
    private string $publicPath = self::DEFAULT_PATH;

    /**
     * @var array|string[]
     */
    private static array $inputTypes = ['mp3', 'wav', 'flac', 'ogg', 'opus', 'oga', 'dat'];

    /**
     * @var array|string[]
     */
    private static array $outputTypes = ['wav', 'dat', 'png', 'json'];

    public function __construct()
    {
        $this->detectAndSetPath();
    }

    /**
     * Helper method to automagically find the audiowaveform command
     *
     * @return string
     * @throws \Exception
     */
    public function detectAndSetPath()
    {
        $response = Terminal::with([
            'binary' => $this->getBinaryName()])
            ->run('whereis -b {{ $binary }}');

        if (!$response->ok()) {
            $response->throw();
        }

        $result = trim((string) $response);

        if (strlen($result) < strlen($this->getBinaryName())+2) {
            throw new \Exception("Command " . $this->getBinaryName() . " not found. Do you have `audiowaveform` installed?");
        }

        $entries = explode(' ', $result);

        if (count($entries) < 2) {
            throw new \Exception("Command " . $this->getBinaryName() . " not found");
        }

        $this->setPublicPath(dirname($entries[1]));

        return "Using `{$this->binaryName}` from `{$this->publicPath}`.";
    }

    /**
     * Returns version number of audiowaveform
     *
     * @return string
     */
    public function getVersion()
    {
        $this->addParam('version');

        return (string) $this->execute();
    }

    /**
     * @return string
     */
    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    /**
     * @param string $publicPath
     */
    public function setPublicPath(string $publicPath): void
    {
        $this->publicPath = $publicPath;
    }

    /**
     * @return string
     */
    public function getBinaryName(): string
    {
        return $this->binaryName;
    }

    /**
     * @param string $binaryName
     */
    public function setBinaryName(string $binaryName): void
    {
        $this->binaryName = $binaryName;
    }

    /**
     * When creating a waveform image, specifies the color scheme to use. Valid values are either audacity, which generates a blue waveform on a grey background, similar to Audacity,
     * or audition, which generates a green waveform on a dark background, similar to Adobe Audition.
     *
     * @param string $color
     * @return void
     */
    public function setColors(string $color)
    {
        if (!in_array($color, ['audacity', 'audition'])) {
            throw new \InvalidArgumentException("The color you passed is not valid. Allowed colors are 'audacity' or 'audition'.");
        }
        $this->addParam(['colors' => $color]);
    }

    /**
     * When creating a waveform image, specifies the waveform color. If not given, the default color used is controlled by the setColors method.
     *
     * @param string $color
     * @return void
     */
    public function setWaveformColor(string $color)
    {
        $this->addParam(['waveform-color' => $this->checkRgbColorCode($color)]);
    }

    /**
     * When creating a waveform image, specifies the border color.
     * If not given, the default color used is controlled by the --colors option.
     *
     * The color value should include two hexadecimal digits for each of red, green, and blue (00 to FF),
     * and optional alpha transparency (00 to FF).
     *
     * @param string $color
     * @return void
     */
    public function setBorderColor(string $color)
    {
        $this->addParam(['waveform-color' => $this->checkRgbColorCode($color)]);
    }

    /**
     * When creating a waveform image, specifies the background color.
     * If not given, the default color used is controlled by the setColors() method.
     *
     * @see setColors
     * @param string $color
     * @return void
     */
    public function setBackgroundColor(string $color)
    {
        $this->addParam(['background-color' => $this->checkRgbColorCode($color)]);
    }

    /**
     * When creating a waveform image, specifies the axis labels color.
     * If not given, the default color used is controlled by the setColors() method.
     *
     * @param string $color
     * @return void
     */
    public function setAxisLabelColor(string $color)
    {
        $this->addParam(['axis-label-color' => $this->checkRgbColorCode($color)]);
    }

    /**
     * Disables status messages.
     *
     * @return void
     */
    public function setQuiet()
    {
        $this->addParam('quiet');
    }

    /**
     * Input filename, which should be a MP3, WAV, FLAC, Ogg Vorbis, or Opus audio file,
     * or a binary waveform data file. By default, audiowaveform uses the file extension
     * to decide how to read the input  file  (either .mp3, .wav, .flac, .ogg, --input-format option.
     * If the --input-filename option is - or is omitted, audiowaveform reads from standard input,
     * and the --input-format option must be used to specify the data format.
     *
     * Note that Opus support requires libsndfile 1.0.29 or later, so may not be available on all systems.
     *
     * @param string $name
     * @return void
     */
    public function setInputFilename(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$inputTypes)) {
            throw new \InvalidArgumentException("File does not have one the allowed input types (" . implode(", ", static::$inputTypes) . ")");
        }

        $this->addParam(['input-filename' => $name]);
    }

    public function setInputFormat(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$inputTypes)) {
            throw new \InvalidArgumentException("Input format does not have one the allowed types (" . implode(", ", self::$inputTypes) . ")");
        }

        $this->addParam(['input-format' => $name]);
    }

    public function setOutputFilename(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$outputTypes)) {
            throw new \InvalidArgumentException("File does not have one the allowed input types (" . implode(", ", self::$outputTypes) . ")");
        }

        $this->addParam(['output-filename' => $name]);
    }

    public function setOutputFormat(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$outputTypes)) {
            throw new \InvalidArgumentException("Output format does not have one the allowed types (" . implode(", ", self::$outputTypes) . ")");
        }

        $this->addParam(['output-format' => $name]);
    }

    public function setSplitChannels()
    {
        $this->addParam('split-channels');
    }

    public function setZoom(Int $level)
    {
        $this->addParam(['zoom' => $level]);
    }

    public function setPixelsPerSecond(Int $pixels)
    {
        $this->addParam(['pixels-per-second' => $pixels]);
    }

    public function setBits(Int $bits)
    {
        if (!in_array($bits, [8, 16])) {
            throw new \InvalidArgumentException("Bits do not have allowed value (8 or 16).");
        }

        $this->addParam(['bits' => $bits]);
    }

    public function setStart(Int $start)
    {
        $this->addParam(['start' => $start]);
    }

    public function setEnd(Int $end)
    {
        $this->addParam(['end' => $end]);
    }

    public function setWidth(Int $width)
    {
        $this->addParam(['width' => $width]);
    }

    public function setHeight(Int $height)
    {
        $this->addParam(['height' => $height]);
    }

    public function setNoAxisLabels()
    {
        $this->addParam('no-axis-labels');
    }

    public function setWithAxisLabels()
    {
        $this->addParam('with-axis-labels');
    }

    public function setAmplitudeScale(Float $scale)
    {
        $this->addParam(['amplitude-scale' => $scale]);
    }

    public function setCompression(Int $level)
    {
        $this->addParam(['compression' => $level]);
    }

    private function execute()
    {
        $response = Terminal::with([
                'exec' => $this->getExecutable(),
                'params' => implode(" ", $this->params)
            ]
        )
            ->run('{{ $exec }} {{ $params }}');

        if (!$response->ok()) {
            $response->throw();
        }

        return $response;
    }

    private function checkRgbColorCode(string $color)
    {
        if (strlen($color) != 6 && strlen($color) != 8) {
            throw new \InvalidArgumentException("The color you passed is not valid. Allowed colors have to be rrggbb or rrggbbaa.");
        }

        return $color;
    }

    private function getExecutable()
    {
        return $this->getPublicPath() . DIRECTORY_SEPARATOR . $this->getBinaryName();
    }

    private function addParam($param)
    {
        if (is_string($param)) {
            $this->params[] = '--' . $param . ' ';
        } else {
            $this->params[] = '--' . key($param) . ' ' . $param[0] . ' ';
        }
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

}
