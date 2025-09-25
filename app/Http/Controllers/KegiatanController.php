<?php

namespace App\Http\Controllers;

use App\Http\Services\ImageKitServices;
use App\Models\Ingpo;
use App\Models\Kegiatan;
use App\Models\videokegiatan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class KegiatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $kegiatan = Kegiatan::all();
        $video = videokegiatan::all();
        $ingpo = Ingpo::all();

        return view('admin.kegiatan', ['title' => 'Kegiatan'], compact('kegiatan', 'user', 'video', 'ingpo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video_link' => 'nullable|url',
            'video_path' => 'nullable|mimes:mov,mp4,avi,mkv|max:20480',
        ]);

        $imageSuccess = false;
        $videoSuccess = false;

        /* ----------  images -> ImageKit  ---------- */
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $index => $file) {
                $imageName   = 'kegiatan-' . time() . '-' . $index . '.' . $file->getClientOriginalExtension();
                $fileContent = file_get_contents($file->getRealPath());

                $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $imageName);

                if (!$uploaded->result) {
                    throw new \Exception('ImageKit image upload failed: ' . ($uploaded->err->message ?? ''));
                }

                Kegiatan::create(['image_path' => $uploaded->result->url]);
            }
            $imageSuccess = true;
        }

        /* ----------  video file -> ImageKit  ---------- */
        if ($request->hasFile('video_path')) {
            $videoFile   = $request->file('video_path');
            $videoName   = 'kegiatan-' . time() . '.' . $videoFile->getClientOriginalExtension();
            $fileContent = file_get_contents($videoFile->getRealPath());

            $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $videoName);

            if (!$uploaded->result) {
                throw new \Exception('ImageKit video upload failed: ' . ($uploaded->err->message ?? ''));
            }

            VideoKegiatan::create(['video_path' => $uploaded->result->url]);
            $videoSuccess = true;
        }

        // Proses YouTube link jika ada
        if ($request->filled('video_link')) {
            $embedUrl = $this->convertYoutubeLinkToEmbed($request->video_link);
            if (!$embedUrl) {
                return redirect()->back()->withErrors(['video_link' => 'Invalid YouTube URL.']);
            }
            VideoKegiatan::create([
                'video_link' => $embedUrl,
            ]);
            $videoSuccess = true;
        }

        // Menyiapkan pesan sukses berdasarkan hasil
        if ($imageSuccess) {
            session()->flash('success_image', 'Images uploaded successfully!');
        }
        if ($videoSuccess) {
            session()->flash('success_video', 'Video uploaded successfully!');
        }

        return redirect()->back();
    }



    /**
     * Convert a YouTube link to an embeddable URL.
     */
    private function convertYoutubeLinkToEmbed($url)
    {
        // Match and extract video ID from various YouTube URL patterns
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtube\.com\/.*v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
        } else {
            return null;  // Return null if the URL doesn't match YouTube patterns
        }

        // Construct and return the embeddable URL
        return 'https://www.youtube.com/embed/' . $videoId;
    }

    // private function processVideoFile($file)
    // {
    //     FFMpeg::fromDisk('local')
    //         ->open($file)
    //         ->export()
    //         ->toDisk('local')
    //         ->inFormat(new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264'))
    //         ->save('videos/compressed/' . $file->getClientOriginalName());
    //     try {
    //         $originalPath = $file->storeAs('videos/original', $file->getClientOriginalName(), 'public');
    //         $compressedPath = 'videos/compressed/' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_compressed.mp4';

    //         $ffmpeg = FFProbe::create([
    //             'ffmpeg.binaries'  => '/path/to/ffmpeg',  // Path to ffmpeg executable
    //             'ffprobe.binaries' => '/path/to/ffprobe', // Path to ffprobe executable
    //             'timeout'          => 3600,              // Optional: timeout in seconds
    //             'ffmpeg.threads'   => 12,                // Optional: number of threads
    //         ]);
    //         $video = $ffmpeg->open(storage_path('app/public/' . $originalPath));
    //         $video->filters()->resize(new FFMpeg\Coordinate\Dimension(1280, 720))->synchronize();
    //         $video->save(new FFMpeg\Format\Video\X264(), storage_path('app/public/' . $compressedPath));

    //         return $compressedPath;
    //     } catch (RuntimeException $e) {
    //         return redirect()->back()->withErrors(['file_video' => 'Video compression failed: ' . $e->getMessage()]);
    //     }
    // }


    /**
     * Update the specified resource in storage.
     */
    public function updateImage(Request $request, $id)
{
    $kegiatan = Kegiatan::findOrFail($id);

    $request->validate([
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($request->hasFile('image')) {
        
        if ($kegiatan->image_path) {
            preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $kegiatan->image_path, $m);
            $oldFileId = $m[1] ?? null;
            if ($oldFileId) {
                app(ImageKitServices::class)->ImageKitDelete($oldFileId);
            }
        }

      
        $file        = $request->file('image');
        $imageName   = 'kegiatan-' . time() . '-' . $kegiatan->id . '.' . $file->getClientOriginalExtension();
        $fileContent = file_get_contents($file->getRealPath());

        $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $imageName);
        if (!$uploaded->result) {
            throw new \Exception('ImageKit image update failed: ' . ($uploaded->err->message ?? ''));
        }

        $kegiatan->image_path = $uploaded->result->url;
        $kegiatan->save();
    }

    return redirect()->back()->with('success', 'Images Updated Successfully.');
}

   public function updateVideo(Request $request, $id)
{
    $video = VideoKegiatan::findOrFail($id);

    $request->validate([
        'video_path' => 'nullable|file|mimes:mov,mp4,avi,mkv|max:20480',
        'video_link' => 'nullable|url',
    ]);

    
    if ($request->hasFile('video_path')) {
        /* delete old video from ImageKit */
        if ($video->video_path) {
            preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $video->video_path, $m);
            $oldFileId = $m[1] ?? null;
            if ($oldFileId) {
                app(ImageKitServices::class)->ImageKitDelete($oldFileId);
            }
        }

        $file        = $request->file('video_path');
        $videoName   = 'kegiatan-' . time() . '.' . $file->getClientOriginalExtension();
        $fileContent = file_get_contents($file->getRealPath());

        $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $videoName);
        if (!$uploaded->result) {
            throw new \Exception('ImageKit video update failed: ' . ($uploaded->err->message ?? ''));
        }

        $video->video_path = $uploaded->result->url;
        $video->video_link = null;          
    }
   
    
    elseif ($request->filled('video_link')) {
        /* delete old video file if exists */
        if ($video->video_path) {
            preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $video->video_path, $m);
            $oldFileId = $m[1] ?? null;
            if ($oldFileId) {
                app(ImageKitServices::class)->ImageKitDelete($oldFileId);
            }
        }

        $embedUrl = $this->convertYoutubeLinkToEmbed($request->video_link);
        if (!$embedUrl) {
            return redirect()->back()->withErrors(['video_link' => 'Invalid YouTube URL.']);
        }

        $video->video_link = $embedUrl;
        $video->video_path = null;          // reset file
    } else {
        return redirect()->back()->withErrors([
            'video_path' => 'Please provide a valid video file or YouTube URL.',
            'video_link' => 'Please provide a valid video file or YouTube URL.',
        ]);
    }

    $video->save();
    return redirect()->back()->with('success', 'Video updated successfully.');
}


    /**
     * Remove the specified resource from storage.
     */
   public function destroyImage($id)
{
    $kegiatan = Kegiatan::findOrFail($id);

    if ($kegiatan->image_path) {
        preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $kegiatan->image_path, $m);
        $fileId = $m[1] ?? null;
        if ($fileId) {
            app(ImageKitServices::class)->ImageKitDelete($fileId);
        }
    }

    $kegiatan->delete();
    return redirect()->back()->with('success', 'Images Deleted Successfully.');
}

    public function destroyVideo($id)
{
    $video = VideoKegiatan::findOrFail($id);

    if ($video->video_path) {
        preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $video->video_path, $m);
        $fileId = $m[1] ?? null;
        if ($fileId) {
            app(ImageKitServices::class)->ImageKitDelete($fileId);
        }
    }

    $video->delete();
    return redirect()->back()->with('success', 'Video Deleted Successfully.');
}
}
