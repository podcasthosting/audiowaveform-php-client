This project is straight forward to use.
It implements all the arguments from the commandline audiowaveform command as methods.
T he script calls the shell command. So audiowaveform has to be installed for this script to work.

Create a new instance of the AudiowaveformClient class to work with it. It automatically tries to detect where the audiowaveform command is installed.

`$ac = new AudiowaveformClient();`

Get the version of the installed audiowaveform:

`echo $ac->getVersion();` 
