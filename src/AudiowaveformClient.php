<?php
/**
 * User: Fabio Bacigalupo
 * Date: 13.11.22
 * Time: 21:46
 */

namespace podcasthosting\AudiowaveformClient;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * # audiowaveform --help
 * AudioWaveform v1.10.3
 *
 * Usage:
 * audiowaveform [options]
 *
* Options:
  * --help                          show help message
* -v [ --version ]                show version information
* -q [ --quiet ]                  disable progress and information messages
* -i [ --input-filename ] arg     input file name (.mp3, .wav, .flac, .ogg,
                                  * .oga, .opus, .dat)
  * -o [ --output-filename ] arg    output file name (.wav, .dat, .png, .json)
  * --split-channels                output multi-channel waveform data or image
                                  * files
                                  * --input-format arg              input file format (mp3, wav, flac, ogg, opus,
    * dat)
* --output-format arg             output file format (wav, dat, png, json)
* -z [ --zoom ] arg (=256)        zoom level (samples per pixel)
  * --pixels-per-second arg (=100)  zoom level (pixels per second)
  * -b [ --bits ] arg (=16)         bits (8 or 16)
* -s [ --start ] arg (=0)         start time (seconds)
* -e [ --end ] arg (=0)           end time (seconds)
* -w [ --width ] arg (=800)       image width (pixels)
* -h [ --height ] arg (=250)      image height (pixels)
* -c [ --colors ] arg (=audacity) color scheme (audition or audacity)
* --border-color arg              border color (rrggbb[aa])
* --background-color arg          background color (rrggbb[aa])
* --waveform-color arg            wave color (rrggbb[aa])
* --waveform-style arg (=normal)  waveform style (normal or bars)
* --bar-width arg (=8)            bar width (pixels)
* --bar-gap arg (=4)              bar gap (pixels)
* --bar-style arg (=square)       bar style (square or rounded)
* --axis-label-color arg          axis label color (rrggbb[aa])
* --no-axis-labels                render waveform image without axis labels
* --with-axis-labels              render waveform image with axis labels
* (default)
* --amplitude-scale arg (=1.0)    amplitude scale
* --compression arg (=-1)         PNG compression level: 0 (none) to 9 (best),
                                  * or -1 (default)
* --raw-samplerate arg            sample rate for raw audio input (Hz)
* --raw-channels arg              number of channels for raw audio input
* --raw-format arg                format for raw audio input (s8, u8, s16le, s16be, s24le, s24be, s32le, s32be, f32le, f32be, f64le, f64be)
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
     * @var array
     */
    private array $params = [];

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
     * @throws ProcessFailedException
     */
    public function detectAndSetPath()
    {
        $process = new Process(['whereis', '-b ',  $this->getBinaryName()]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $result = trim((string) $process->getOutput());

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
    public function setPublicPath(string $publicPath): AudiowaveformClient
    {
        $this->publicPath = $publicPath;

        return $this;
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
    public function setBinaryName(string $binaryName): AudiowaveformClient
    {
        $this->binaryName = $binaryName;

        return $this;
    }

    /**
     * When creating a waveform image, specifies the color scheme to use. Valid values are either audacity, which generates a blue waveform on a grey background, similar to Audacity,
     * or audition, which generates a green waveform on a dark background, similar to Adobe Audition.
     *
     * @param string $color
     * @return AudiowaveformClient
     */
    public function setColors(string $color)
    {
        if (!in_array($color, ['audacity', 'audition'])) {
            throw new \InvalidArgumentException("The color you passed is not valid. Allowed colors are 'audacity' or 'audition'.");
        }
        $this->addParam(['colors' => $color]);

        return $this;
    }

    /**
     * When creating a waveform image, specifies the waveform color. If not given, the default color used is controlled by the setColors method.
     *
     * @param string $color
     * @return AudiowaveformClient
     */
    public function setWaveformColor(string $color)
    {
        $this->addParam(['waveform-color' => $this->checkRgbColorCode($color)]);

        return $this;
    }

    /**
     * waveform style (normal or bars)
     *
     * @param string $style
     * @return $this
     */
    public function setWaveformStyle(string $style = 'normal')
    {
        $this->addParam(['waveform-style' => in_array($style, ['normal', 'bars']) ? $style : 'normal']);

        return $this;
    }

    /**
     * bar style (square or rounded)
     *
     * @param string $style
     * @return $this
     */
    public function setBarStyle(string $style = 'square')
    {
        $this->addParam(['bar-style' => in_array($style, ['square', 'rounded']) ? $style : 'square']);

        return $this;
    }

    /**
     * bar width (pixels)
     *
     * @param int $width
     * @return $this
     */
    public function setBarWidth(int $width = 8)
    {
        $this->addParam(['bar-width' => $width]);

        return $this;
    }

    /**
     * bar gap (pixels)
     *
     * @param int $width
     * @return $this
     */
    public function setBarGap(int $width = 4)
    {
        $this->addParam(['bar-width' => $width]);

        return $this;
    }

    /**
     * sample rate for raw audio input (Hz)
     *
     * @param int $rate
     * @return $this
     */
    public function setRawSamplerate(int $rate)
    {
        $this->addParam(['raw-samplerate' => $rate]);

        return $this;
    }

    /**
     * number of channels for raw audio input
     *
     * @param int $channels
     * @return $this
     */
    public function setRawChannels(int $channels)
    {
        $this->addParam(['raw-channels' => $channels]);

        return $this;
    }

    /**
     * format for raw audio input (s8, u8, s16le,
     * s16be, s24le, s24be, s32le, s32be, f32le,
     * f32be, f64le, f64be)
     *
     * @param string $format
     * @return $this
     */
    public function setRawFormat(string $format)
    {
        $this->addParam(['raw-format' => in_array($format, ['s8', 'u8', 's16le', 's16be', 's24le', 's24be', 's32le', 's32be', 'f32le', 'f32be', 'f64le', 'f64be']) ? $format : 's8']);

        return $this;
    }

    /**
     * When creating a waveform image, specifies the border color.
     * If not given, the default color used is controlled by the --colors option.
     *
     * The color value should include two hexadecimal digits for each of red, green, and blue (00 to FF),
     * and optional alpha transparency (00 to FF).
     *
     * @param string $color
     * @return AudiowaveformClient
     */
    public function setBorderColor(string $color)
    {
        $this->addParam(['waveform-color' => $this->checkRgbColorCode($color)]);

        return $this;
    }

    /**
     * When creating a waveform image, specifies the background color.
     * If not given, the default color used is controlled by the setColors() method.
     *
     * @param string $color
     * @return AudiowaveformClient
     * @see setColors
     */
    public function setBackgroundColor(string $color)
    {
        $this->addParam(['background-color' => $this->checkRgbColorCode($color)]);

        return $this;
    }

    /**
     * When creating a waveform image, specifies the axis labels color.
     * If not given, the default color used is controlled by the setColors() method.
     *
     * @param string $color
     * @return AudiowaveformClient
     */
    public function setAxisLabelColor(string $color)
    {
        $this->addParam(['axis-label-color' => $this->checkRgbColorCode($color)]);

        return $this;
    }

    /**
     * Disables status messages.
     *
     * @return AudiowaveformClient
     */
    public function setQuiet()
    {
        $this->addParam('quiet');

        return $this;
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
     * @return AudiowaveformClient
     */
    public function setInputFilename(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$inputTypes)) {
            throw new \InvalidArgumentException("File does not have one the allowed input types (" . implode(", ", static::$inputTypes) . ")");
        }

        $this->addParam(['input-filename' => escapeshellarg($name)]);

        return $this;
    }

    /**
     * @param string $name
     * @return AudiowaveformClient
     */
    public function setInputFormat(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$inputTypes)) {
            throw new \InvalidArgumentException("Input format does not have one the allowed types (" . implode(", ", self::$inputTypes) . ")");
        }

        $this->addParam(['input-format' => $name]);

        return $this;
    }

    /**
     * @param string $name
     * @return AudiowaveformClient
     */
    public function setOutputFilename(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$outputTypes)) {
            throw new \InvalidArgumentException("File does not have one the allowed input types (" . implode(", ", self::$outputTypes) . ")");
        }

        $this->addParam(['output-filename' => escapeshellarg($name)]);

        return $this;
    }

    /**
     * @param string $name
     * @return AudiowaveformClient
     */
    public function setOutputFormat(string $name)
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::$outputTypes)) {
            throw new \InvalidArgumentException("Output format does not have one the allowed types (" . implode(", ", self::$outputTypes) . ")");
        }

        $this->addParam(['output-format' => $name]);

        return $this;
    }

    /**
     * @return AudiowaveformClient
     */
    public function setSplitChannels()
    {
        $this->addParam('split-channels');

        return $this;
    }

    /**
     * @param Int $level
     * @return AudiowaveformClient
     */
    public function setZoom(Int $level)
    {
        $this->addParam(['zoom' => $level]);

        return $this;
    }

    /**
     * @param Int $pixels
     * @return AudiowaveformClient
     */
    public function setPixelsPerSecond(Int $pixels)
    {
        $this->addParam(['pixels-per-second' => $pixels]);

        return $this;
    }

    /**
     * @param Int $bits
     * @return AudiowaveformClient
     */
    public function setBits(Int $bits)
    {
        if (!in_array($bits, [8, 16])) {
            throw new \InvalidArgumentException("Bits do not have allowed value (8 or 16).");
        }

        $this->addParam(['bits' => $bits]);

        return $this;
    }

    /**
     * @param Int $start
     * @return AudiowaveformClient
     */
    public function setStart(Int $start)
    {
        $this->addParam(['start' => $start]);

        return $this;
    }

    /**
     * @param Int $end
     * @return AudiowaveformClient
     */
    public function setEnd(Int $end)
    {
        $this->addParam(['end' => $end]);

        return $this;
    }

    /**
     * @param Int $width
     * @return AudiowaveformClient
     */
    public function setWidth(Int $width)
    {
        $this->addParam(['width' => $width]);

        return $this;
    }

    /**
     * @param Int $height
     * @return AudiowaveformClient
     */
    public function setHeight(Int $height)
    {
        $this->addParam(['height' => $height]);

        return $this;
    }

    /**
     * @return AudiowaveformClient
     */
    public function setNoAxisLabels()
    {
        $this->addParam('no-axis-labels');

        return $this;
    }

    /**
     * @return AudiowaveformClient
     */
    public function setWithAxisLabels()
    {
        $this->addParam('with-axis-labels');

        return $this;
    }

    /**
     * @param Float $scale
     * @return AudiowaveformClient
     */
    public function setAmplitudeScale(Float $scale)
    {
        $this->addParam(['amplitude-scale' => $scale]);

        return $this;
    }

    /**
     * @param Int $level
     * @return AudiowaveformClient
     */
    public function setCompression(Int $level)
    {
        if ($level < -1 || $level > 9) {
            throw new \InvalidArgumentException("Compression has to be between -1 (default) and 9 (best).");
        }

        $this->addParam(['compression' => $level]);

        return $this;
    }

    public function execute(int $timeout = 120)
    {
        $process = new Process([$this->getExecutable(), implode(", ", $this->params)]);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
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
            $this->params[] = '--' . $param;
        } else {
            $this->params[] = '--' . key($param) . '=' . current($param);
        }
    }
}