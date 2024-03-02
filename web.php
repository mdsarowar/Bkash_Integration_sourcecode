Route::post('create/bkash',[SarowarBkashController::class,'createPayment'])->name('bkash-create-payment');
Route::get('/bkash/pay',[SarowarBkashController::class,'callBack'])->name('excute_payment');

//search payment
    Route::get('/bkashnew/search/{trxID}', [SarowarBkashController::class,'bkashSearch'])->name('bkash-serach');
