<?php

namespace App\Services;

use ApiResponse;
use App\Models\ProductImage;
use Helper;
use Illuminate\Http\Request;

/**
 * Class ProductService.
 */
class ProductService
{
    protected $pathVariant = 'product_variants';

    public function ProductValidation(Request $req)
    {
        return validator($req->all(), [
            'name' => 'required|string|max:100',
            'name_km' => 'nullable|string|max:100',
            'category_id' => 'required',
            'brand_id' => 'required',
            'code' => 'nullable|string|max:50',
            'thumbnail' => 'nullable',
            'description' => 'nullable',
            'status' => 'nullable',
            'price' => 'nullable',
            'variants' => 'nullable|array',
        ]);
    }

    public function productVariantValidator(Request $req)
    {
        return validator($req->all(), [
            'product_id' => 'required|int',
            'color_id' => 'nullable|int',
            'size_id' => 'nullable|int',
            'qty' => 'required|int|min:1',
            'price' => 'required|numeric|min:0',
            'photos' => 'required|array'
        ]);
    }

    public function variantPhotoValidation(Request $req)
    {
        return validator($req->all(), [
            'variant_id' => 'nullable',
            'photo' => 'nullable|string'
        ]);
    }

    public function updateOrCreateVariantPhotos($photos, $variantId, $user)
    {
        $processedIds = [];
        foreach ($photos as $photo) {
            $id = $photo['id'] ?? null;
            if ($id && in_array($id, $processedIds)) {
                continue;
            }
            $photo['variant_id'] = $variantId;
            $request = new Request($photo);
            $validate = $this->variantPhotoValidation($request);
            if ($validate->fails())  return ApiResponse::ValidateFail($validate->errors()->first());
            $inputs = $validate->validated();
            $file_name = Helper::base64ToImageFile($inputs['photo'] ?? '', 1, $this->pathVariant);
            $inputs['photo_file_name'] = is_object($file_name) ? $file_name->filename : $file_name;
            $inputs['variant_id'] = $variantId;
            unset($inputs['photo']);
            if ($id) {
                $processedIds[] = $id;
                $existingPhoto = ProductImage::find($id);
                if (!$existingPhoto) {
                    Helper::deleteImageFile($inputs['photo_file_name'], 1, $this->pathVariant);
                    return ApiResponse::ValidateFail('Failed to update photo: Photo not found');
                }
                $oldFileName = $existingPhoto->photo_file_name;
                $update = $existingPhoto->update($inputs);
                if (!$update) {
                    Helper::deleteImageFile($inputs['photo_file_name'], 1, $this->pathVariant);
                    return ApiResponse::ValidateFail('Failed to update photo');
                }
                Helper::deleteImageFile($oldFileName, 1, $this->pathVariant);
            }
            else {
                $newPhoto = ProductImage::create($inputs);
                if (!$newPhoto) {
                    Helper::deleteImageFile($inputs['photo_file_name'], 1, $this->pathVariant);
                    return ApiResponse::ValidateFail('Failed to add photo');
                }
            }
        }
        return ApiResponse::JsonResult(null,'Success');
    }
}
