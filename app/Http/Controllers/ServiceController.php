<?php

namespace App\Http\Controllers;

use App\Http\Services\ImageKitServices;
use App\Models\Ingpo;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $service = Service::all();
        $ingpo = Ingpo::all();

        return view('admin.services_view', [
            'title' => 'Service'
        ], compact('service', 'user', 'ingpo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'img' => 'required|max:5120',
            'judul' => 'required|string|max:255',
            'desc' => 'required|string|max:5000',
        ]);

        /* ---------- upload to ImageKit ---------- */
            $file        = $request->file('img');
            $imageName   = uniqid() . '.' . $file->getClientOriginalExtension();
            $fileContent = file_get_contents($file->getRealPath());

            $uploaded = app(ImageKitServices::class)
                        ->ImageKitUpload($fileContent, $imageName);

            if (!$uploaded->result) {
                throw new \Exception('ImageKit upload failed: '
                    . ($uploaded->err->message ?? 'Unknown error'));
            }

            /* ---------- save URL to DB ---------- */
            Service::create([
                'img'   => $uploaded->result->url,   // <- ImageKit URL
                'judul' => $request->judul,
                'desc'  => $request->desc,
            ]);

            return redirect()->route('services.index')
                            ->with('success', 'Service added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $request->validate([
        'img'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        'judul' => 'required|string',
        'desc'  => 'required|string',
    ]);

    $service = Service::findOrFail($id);

    
    if ($request->hasFile('img')) {
       
        if ($service->img) {
           
            preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $service->img, $m);
            $fileId = $m[1] ?? null;
            if ($fileId) {
                app(ImageKitServices::class)->ImageKitDelete($fileId);
            }
        }

       
        $file        = $request->file('img');
        $imageName   = $id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $fileContent = file_get_contents($file->getRealPath());

        $uploaded = app(ImageKitServices::class)->ImageKitUpdate($fileContent, $imageName);
        if (!$uploaded->result) {
            throw new \Exception('ImageKit update failed: ' . ($uploaded->err->message ?? ''));
        }

        $service->img = $uploaded->result->url;   // save ImageKit URL
    }

    
    $service->judul = $request->judul;
    $service->desc  = $request->desc;
    $service->save();

    return redirect()->route('services.index')
                     ->with('success', 'Service updated successfully.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        if ($service->img) {
            preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $service->img, $m);
            $fileId = $m[1] ?? null;
            if ($fileId) {
                app(ImageKitServices::class)->ImageKitDelete($fileId);
            }
        }

        $service->delete();

        return redirect()->route('services.index')
                        ->with('success', 'Service deleted successfully.');
    }
}
