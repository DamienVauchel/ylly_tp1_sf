Ylly TP1: Translator
===========

Auto translation learning project by [Ylly agency](https://ylly.fr)

## Installation

Use makefile

## Use

### Command

    php bin/console app:ninja-translator <arguments> <options>
    
You can not type all "app:ninja-translator" by typing "a:n" if you have no other command beginning by a and n.

#### Arguments

The command arguments are the language codes you want to translate to. For example french, portuguese and spanish:
    
    php bin/console a:n fr pt es 
    
For language codes, please see the [Google documentation](https://cloud.google.com/translate/docs/languages).

#### Options

##### From

You can specify the language to translate from by typing, for example with french

    php bin/console a:n --from=fr
    
If no option, default "from" language is english.

##### Entities

You can specify the entities you want to translate. For example Article and Post

    php bin/console a:n --entity=Article --entity=Post
    
If no option, default is for all (--entity=all).
