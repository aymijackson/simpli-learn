<?php

/*
| Interface languages. Each has lang/{code}.json (English text => translation);
| English is the source text itself. To add a language (e.g. Efik "efi" or
| Ibibio "ibb"): copy lang/fr.json to lang/{code}.json, translate the values,
| then add the code here. Untranslated entries fall back to English.
*/
return [

    'available' => [
        'en' => 'English',
        'fr' => 'Français',
        'yo' => 'Yorùbá',
        'ha' => 'Hausa',
        'ig' => 'Igbo',
        'pcm' => 'Naijá (Pidgin)',
    ],

    /*
    | Languages whose translations are machine-drafted and should be checked
    | by a native speaker before being relied on. Shown as "(beta)".
    */
    'beta' => ['yo', 'ha', 'ig', 'pcm'],

];
