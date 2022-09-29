<?php
use Illuminate\Support\Facades\Route;

Route::middleware('web', 'auth:web')->namespace('\Hanoivip\Epinkasa')->group(function () {
    Route::get('/epinkasa/start', function () {
        return redirect()->route('wizard.role', [
            'next' => 'epinkasa.do'
        ]);
    })->name('epinkasa');
    Route::get('/epinkasa/do', 'EpinkasaController@redirect')->name('epinkasa.do');
});

Route::any('/epinkasa', 'EpinkasaController@callback');