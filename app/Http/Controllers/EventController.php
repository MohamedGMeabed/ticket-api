<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        return Event::query()->orderBy('starts_at')->get();
    }

    public function show(Event $event)
    {
        return $event->load('seats');
    }

    public function seats(Request $request, Event $event)
    {
        $query = $event->seats();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('section')->orderBy('row')->orderBy('number')->get();
    }
}
