<?php

namespace App\View\Components;

use Illuminate\View\View;
use Illuminate\View\Component;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;

class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|View
     */
    public function render()
    {
        return view('layouts.guest');
    }
}
