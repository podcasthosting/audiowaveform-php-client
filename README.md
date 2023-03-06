This project is straight forward to use.
It implements all the arguments from the commandline audiowaveform command as methods.
The script calls the shell command. So audiowaveform has to be installed for this script to work.

Create a new instance of the AudiowaveformClient class to work with it. It automatically tries to detect where the audiowaveform command is installed.

`$ac = new AudiowaveformClient();`

Get the version of the installed audiowaveform:

`echo $ac->getVersion();` 

The `__constructor()` method calls a method called `detectAndSetPath()` to find the `audiowaveform` binary.

You can override the name of the binary by using the setter method `setBinaryName(string $binaryName)`.

All parameters have their own setter method:

- `setColors(string $color)`
- `setWaveformColor(string $color)`
- `setBorderColor(string $color)`
- `setBackgroundColor(string $color)`
- `setAxisLabelColor(string $color)`
- `setInputFilename(string $name)`
- `setInputFormat(string $name)`
- `setOutputFilename(string $name)`
- `setSplitChannels()`
- `setPixelsPerSecond(Int $pixels)`
- `setBits(Int $bits)`
- `setStart(Int $start)`
- `setEnd(Int $end)`
- `setWidth(Int $width)`
- `setHeight(Int $height)`
- `setNoAxisLabels()`
- `setWithAxisLabels()`
- `setAmplitudeScale(Float $scale)`
- `setCompression(Int $level)`
- `setCompression(Int $level)`
- `setQuiet()`

`setInputFilename()` and `setOutputFilename()` are required.

After you have set all parameters you have to call the `execute()` method to run the program, e.g.

```
$ac = new AudiowaveformClient()
$ac->setInputFilename('my-audio-file.mp3');
$ac->setBackgroundColor('000000');
$ac->setPixelsPerSecond(300)->execute();
```

You can also chain all setters:

```
(new AudiowaveformClient)->setInputFilename('other-audio.wav')setColors('ff9900')->setPixelsPerSecond(300)->execute();
```
