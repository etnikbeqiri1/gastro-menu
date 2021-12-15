<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Customer;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');


        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $categories = Category::all();
        } else {


            $customers = Customer::where('user_id',Auth::user()->getAuthIdentifier())->get();

            foreach ($customers as $cos){
                $tempArray = Category::where('customer_id',$cos->id)->get();
            }


            //$categories = Category::where('customer_id',Auth::user()->getAuthIdentifier())->get();
            $categories = $tempArray;
        }



        $customers = Customer::get();

        return view('admin.categories.index', compact('categories', 'customers'));
    }

    public function create()
    {
        abort_if(Gate::denies('category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $customers = Customer::all()->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        } else {

            $customers = Customer::where('user_id', Auth::user()->getAuthIdentifier())->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        }
        return view('admin.categories.create', compact('customers'));

    }

    public function store(StoreCategoryRequest $request)
    {
        $category = null;

        if (Auth::user()->Roles->get(0)->title === 'Admin') {
            $category = Category::create($request->all());
        } else {
            // filter categories by customer account
            $category = Category::create($request->all());
        }


        foreach ($request->input('image', []) as $file) {
            $category->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $category->id]);
        }

        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category)
    {
        abort_if(Gate::denies('category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customers = Customer::where('user_id',Auth::user()->getAuthIdentifier())->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $category->load('customer');

        return view('admin.categories.edit', compact('customers', 'category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->all());

        if (count($category->image) > 0) {
            foreach ($category->image as $media) {
                if (!in_array($media->file_name, $request->input('image', []))) {
                    $media->delete();
                }
            }
        }

        $media = $category->image->pluck('file_name')->toArray();

        foreach ($request->input('image', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $category->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection('image');
            }
        }

        return redirect()->route('admin.categories.index');
    }

    public function show(Category $category)
    {
        abort_if(Gate::denies('category_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $category->load('customer', 'categoryProducts');

        return view('admin.categories.show', compact('category'));
    }

    public function destroy(Category $category)
    {
        abort_if(Gate::denies('category_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $category->delete();

        return back();
    }

    public function massDestroy(MassDestroyCategoryRequest $request)
    {
        Category::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('category_create') && Gate::denies('category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new Category();
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
