<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Database\AbstractModel;
use Flarum\Foundation\IconImage\Event\Saving;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

/**
 * The API surface of an icon image, for a resource whose model uses
 * HasIconImage. Add the endpoints and fields to the resource:
 *
 *     public function endpoints(): array
 *     {
 *         return [...IconImageApi::endpoints(), ...];
 *     }
 *
 * `POST /{type}/{id}/icon-image` takes the file as the `iconImage` form field,
 * `DELETE /{type}/{id}/icon-image` clears it. Both return the resource. They
 * save the model directly, not through the resource's saving hooks, and
 * dispatch Event\Saving instead.
 */
class IconImageApi
{
    /**
     * @param string $ability The ability the actor needs on the model.
     * @return Endpoint\Endpoint[]
     */
    public static function endpoints(string $ability = 'edit'): array
    {
        return [
            Endpoint\Endpoint::make('iconImage.upload')
                ->route('POST', '/{id}/icon-image')
                ->authenticated()
                ->can($ability)
                ->action(function (Context $context) {
                    /** @var AbstractModel $model */
                    $model = $context->model;

                    $file = Arr::get($context->request->getUploadedFiles(), 'iconImage');

                    if (! $file instanceof UploadedFileInterface) {
                        $translator = resolve(TranslatorInterface::class);

                        throw new ValidationException([
                            'iconImage' => str_replace(':attribute', 'iconImage', $translator->trans('validation.required')),
                        ]);
                    }

                    resolve(IconImageValidator::class)->assertImageValid('iconImage', $file);

                    $image = resolve(ImageManager::class)->read($file->getStream()->getMetadata('uri'));

                    resolve(Dispatcher::class)->dispatch(new Saving($model, $context->getActor(), $image));

                    $uploader = resolve(IconImageUploader::class);
                    $uploader->upload($model, $image);

                    try {
                        $model->save();
                    } catch (Throwable $e) {
                        // The row still points at the previous image: drop the new files.
                        $uploader->deleteAllVariants($model->getAttribute('icon_image_path'));

                        throw $e;
                    }

                    return $model;
                }),
            Endpoint\Endpoint::make('iconImage.delete')
                ->route('DELETE', '/{id}/icon-image')
                ->authenticated()
                ->can($ability)
                ->action(function (Context $context) {
                    /** @var AbstractModel $model */
                    $model = $context->model;

                    resolve(Dispatcher::class)->dispatch(new Saving($model, $context->getActor(), null));

                    resolve(IconImageUploader::class)->remove($model);

                    $model->save();

                    return $model;
                }),
        ];
    }

    /**
     * @return Schema\Str[]
     */
    public static function fields(): array
    {
        return [
            Schema\Str::make('iconImageUrl')
                ->nullable()
                ->get(fn (AbstractModel $model) => $model->getAttribute('icon_image_url')),
            Schema\Str::make('iconImageSrcset')
                ->nullable()
                ->get(fn (AbstractModel $model) => $model->getAttribute('icon_image_srcset')),
        ];
    }
}
