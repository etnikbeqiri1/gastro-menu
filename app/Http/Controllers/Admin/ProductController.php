<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $products = Product::all();
        } else {

            $customers = Customer::where('user_id', Auth::user()->getAuthIdentifier())->get();



            $products = DB::select('SELECT * FROM products WHERE category_id in (SELECT id from categories where customer_id in (SELECT id from customers where user_id = 2))');
        }



        $categories = Category::get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        abort_if(Gate::denies('product_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $categories = Category::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        } else {

            $customers = Customer::where('user_id', Auth::user()->getAuthIdentifier())->get();

            foreach ($customers as $cos) {
                $tempArray = Category::where('customer_id', $cos->id)->get()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
            }


            //$categories = Category::where()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
            $categories = $tempArray;

        }

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->all());

        foreach ($request->input('image', []) as $file) {
            $product->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $product->id]);
        }

        return redirect()->route('admin.products.index');
    }

    public function edit(Product $product)
    {
        abort_if(Gate::denies('product_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $categories = Category::all();
        } else {

            $customers = Customer::where('user_id', Auth::user()->getAuthIdentifier())->get();

            foreach ($customers as $cos) {
                $tempArray = Category::where('customer_id', $cos->id)->get()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
            }


            //$categories = Category::where()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
            $categories = $tempArray;

        }

        $product->load('category');

        return view('admin.products.edit', compact('categories', 'product'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->all());

        if (count($product->image) > 0) {
            foreach ($product->image as $media) {
                if (!in_array($media->file_name, $request->input('image', []))) {
                    $media->delete();
                }
            }
        }

        $media = $product->image->pluck('file_name')->toArray();

        foreach ($request->input('image', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $product->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection('image');
            }
        }

        return redirect()->route('admin.products.index');
    }

    public function show(Product $product)
    {
        abort_if(Gate::denies('product_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    public function destroy(Product $product)
    {
        abort_if(Gate::denies('product_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $product->delete();

        return back();
    }

    public function massDestroy(MassDestroyProductRequest $request)
    {
        Product::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('product_create') && Gate::denies('product_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Product();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
