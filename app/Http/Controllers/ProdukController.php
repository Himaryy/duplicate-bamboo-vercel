<?php

namespace App\Http\Controllers;

use App\Http\Services\ImageKitServices;
use App\Models\Produk;
use App\Models\User;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Ingpo;
use GuzzleHttp\Client;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = Auth::user();
        $produk = Produk::query();
        $kategori = Kategori::all();
        $ingpo = Ingpo::all();

        // Pagination
        $produkPagination = $produk->paginate(10);
        $produkItems = $produkPagination->items();
        $produkCurrentPage = $produkPagination->currentPage();
        $produkTotalPages = $produkPagination->lastPage();
        $produkFirstPageUrl = $produkPagination->url(1);
        $produkLastPageUrl = $produkPagination->url($produkPagination->lastPage());
        $produkPreviousPageUrl = $produkPagination->previousPageUrl();
        $produkNextPageUrl = $produkPagination->nextPageUrl();


        return view('admin.produk', [
            'title' => 'Produk',
            'ingpo' => $ingpo,
            'produk' => $produkPagination,
            // 'produkPagination' => $produkPagination,
            'produkItems' => $produkItems,
            'produkCurrentPage' => $produkCurrentPage,
            'produkTotalPages' => $produkTotalPages,
            'produkFirstPageUrl' => $produkFirstPageUrl,
            'produkLastPageUrl' => $produkLastPageUrl,
            'produkPreviousPageUrl' => $produkPreviousPageUrl,
            'produkNextPageUrl' => $produkNextPageUrl,
        ], compact('produk', 'user', 'kategori', 'ingpo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $produk = Produk::all();




        return view('form.add-produk', compact('produk'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $pid = $this->generateRandomString1(25);
        // Validasi input, pastikan 'image' adalah file dengan format yang benar
        $request->validate([
            'kode_produk' => 'required|string|unique:produks',
            'nama_produk' => 'required|string|unique:produks',
            'kategori_id' => 'required|string',
            // 'jenis_produk' => 'required|string',
            'jumlah_produk' => 'required|integer',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'image1' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'image2' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'image3' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'image4' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'deskripsi' => 'required|string|max:5000',
            'harga' => 'required|integer',
            'berat' => 'required|integer',
            'tokped' => 'required|string|max:5000',
            'shopee' => 'required|string|max:5000',
        ]);

        $images = [];
        foreach (['image', 'image1', 'image2', 'image3', 'image4'] as $key) {
            if ($request->hasFile($key)) {
                $file        = $request->file($key);
                $imageName   = "{$pid}_{$key}." . $file->getClientOriginalExtension();
                $fileContent = file_get_contents($file->getRealPath());

                $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $imageName);

                if (!$uploaded->result) {
                    throw new \Exception("ImageKit upload failed: " . ($uploaded->err->message ?? ''));
                }
                $images[$key] = $uploaded->result->url; // save ImageKit URL
            }
        }

        /* ----------  create record  ---------- */
        Produk::create([
            'pid'           => $pid,
            'kode_produk'   => $request->kode_produk,
            'nama_produk'   => $request->nama_produk,
            'kategori_id'   => $request->kategori_id,
            'jumlah_produk' => $request->jumlah_produk,
            'image'  => $images['image']  ?? null,
            'image1' => $images['image1'] ?? null,
            'image2' => $images['image2'] ?? null,
            'image3' => $images['image3'] ?? null,
            'image4' => $images['image4'] ?? null,
            'deskripsi' => $request->deskripsi,
            'harga'  => $request->harga,
            'berat'  => $request->berat,
            'tokped' => $request->tokped,
            'shopee' => $request->shopee,
        ]);

        return redirect()->route('produk.index')->with('success', 'Produk Berhasil ditambah');
    }

    private function generateRandomString1($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Display the specified resource.
     */

    // public function getProvinsi()
    // {
    //     $client = new Client();
    //     try {
    //         $response = $client->request(
    //             'POST',
    //             'https://api.rajaongkir.com/starter/province',
    //             array(
    //                 'headers' => array(
    //                     'key' => '1a14ace5f65a3788c0ccf8115baed896'
    //                 )
    //             )
    //         );
    //     } catch (RequestException $e) {
    //         var_dump($e->getResponse()->getBody()->getContents());
    //     }
    //     $json = $response->getBody()->getContents();
    //     $array_result = json_decode($json, true);
    //     print_r($array_result);
    // }

    public function keranjang()
    {
        $produk = Produk::all();
        return view('produk_show.index', compact('produk'));
    }

    public function TambahKeranjang(Request $request, $pid)
    {
        $produk = Produk::find($pid);

        // Get the cart from session or initialize it if it doesn't exist
        $keranjang = session()->get('keranjang', []);

        // If the product already exists in the cart, increase its quantity
        if (isset($keranjang[$pid])) {
            $keranjang[$pid]['quantity']++;
        } else {
            // Add new product to the cart
            $keranjang[$pid] = [
                "nama_produk" => $produk->nama_produk,
                "harga" => $produk->harga,
                "quantity" => 1
            ];
        }

        // Save the updated cart to the session
        session()->put('keranjang', $keranjang);

        // Calculate total items in the cart
        $totalItems = array_sum(array_column($keranjang, 'quantity'));

        // Return the cart count along with a message
        return response()->json([
            'message' => 'Produk ditambahkan ke keranjang!',
            'cart_count' => $totalItems
        ]);
    }

    public function syncCart(Request $request)
    {
        // Save the cart to the session
        session()->put('keranjang', $request->cart);
        return response()->json(['message' => 'Cart updated']);
    }


    public function show($nama_produk, Request $request)
    {
        $nama_produk = str_replace('-', ' ', $nama_produk);
        // $kategori = $request->input('kategori', 'semua');


        $produk = DB::table('produks')->where('nama_produk', $nama_produk)->get();
        $ingpo = Ingpo::all();

        if (!$produk) {
            return redirect()->route('catalog.index')->with('error', 'Produk tidak ditemukan');
        }

        // Ambil 5 produk lainnya secara acak, kecuali produk yang sedang dilihat
        $produkLainnya = DB::table('produks')
            ->where('nama_produk', '!=', $nama_produk)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // if ($kategori == 'semua') {

        //     // Get all products if 'semua' is selected
        //     // $produk = Produk::all();
        //     // Paginate as per your requirement
        // } else {
        //     // Get products filtered by category
        //     $produk = DB::table('produks')
        //         ->where('kategori_id', $kategori);
        // }


        return view('produk_show.index', compact('produk', 'produkLainnya', 'ingpo'));
    }

    public function filterProdukCatalog(Request $request) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produk $produk)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $pid)
{
    $request->validate([
        'kode_produk'   => 'string',
        'nama_produk'   => 'required|string',
        'kategori_id'   => 'required|string',
        'jumlah_produk' => 'required|integer',
        'image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'deskripsi' => 'required|string',
        'harga'  => 'required|integer',
        'tokped' => 'required|string',
        'shopee' => 'required|string',
    ]);

    $produk = Produk::where('pid', $pid)->firstOrFail();

    /* ----------  handle up to 5 images  ---------- */
    foreach (['image', 'image1', 'image2', 'image3', 'image4'] as $key) {
        if ($request->hasFile($key)) {
            /* 1. delete old ImageKit file if exists */
            if ($produk->$key) {
                preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $produk->$key, $m);
                $oldFileId = $m[1] ?? null;
                if ($oldFileId) {
                    app(ImageKitServices::class)->ImageKitDelete($oldFileId);
                }
            }

            /* 2. upload new file to ImageKit */
            $file        = $request->file($key);
            $imageName   = "{$pid}_{$key}_" . time() . '.' . $file->getClientOriginalExtension();
            $fileContent = file_get_contents($file->getRealPath());

            $uploaded = app(ImageKitServices::class)->ImageKitUpload($fileContent, $imageName);
            if (!$uploaded->result) {
                throw new \Exception("ImageKit upload failed: " . ($uploaded->err->message ?? ''));
            }

            $produk->$key = $uploaded->result->url;   // save ImageKit URL
        }
        // else -> keep existing value (no change)
    }

    /* ----------  update other fields  ---------- */
    $produk->update([
        'kode_produk'   => $request->kode_produk,
        'nama_produk'   => $request->nama_produk,
        'kategori_id'   => $request->kategori_id,
        'jumlah_produk' => $request->jumlah_produk,
        'deskripsi'     => $request->deskripsi,
        'harga'         => $request->harga,
        'tokped'        => $request->tokped,
        'shopee'        => $request->shopee,
    ]);

    /* ----------  optional cart update  ---------- */
    if ($request->pid && $request->quantity) {
        $keranjang = session()->get('keranjang', []);
        if (isset($keranjang[$request->pid])) {
            $keranjang[$request->pid]['quantity'] = $request->quantity;
            session()->put('keranjang', $keranjang);
        }
    }

    return redirect()->route('produk.index');
}

    /**
     * Remove the specified resource from storage.
     */
   public function destroy($pid)
{
    $produk = Produk::where('pid', $pid)->first();

    if ($produk) {
        /* ----------  delete each ImageKit file  ---------- */
        foreach (['image', 'image1', 'image2', 'image3', 'image4'] as $field) {
            if ($produk->$field) {
                // extract fileId from URL
                preg_match('/\/([^\/]+?)(?:_[a-zA-Z0-9]+\.[a-z]+)?$/', $produk->$field, $m);
                $fileId = $m[1] ?? null;
                if ($fileId) {
                    app(ImageKitServices::class)->ImageKitDelete($fileId);
                }
            }
        }

        /* ----------  remove DB row  ---------- */
        $produk->delete();
    }

    return redirect()->route('produk.index')
                     ->with('success', 'Produk berhasil dihapus!');
}
    public function remove(Request $request)

    {
        if ($request->pid) {
            $keranjang = session()->get('keranjang');
            if (isset($keranjang[$request->pid])) {
                unset($keranjang[$request->pid]);
                session()->put('keranjang', $keranjang);
            }
            session()->flash('success', 'Product removed successfully');
        }
    }
}
