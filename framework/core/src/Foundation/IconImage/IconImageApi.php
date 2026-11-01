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
use Flarum\Foundation\IconImage\Event\IconImageChanged;
use Flarum\Foundation\IconImage\Event\IconImageSaving;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
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
 * dispatch Event\IconImageSaving before the change and Event\IconImageChanged
 * after it instead.
 *
 * The model's iconImageUploader() stores the image. The endpoints are named
 * `iconImage.upload` and `iconImage.delete`, so an extension can alter them
 * with the ApiResource extender.
 */
class IconImageApi
{
    /**
     * @param string $ability The ability the actor needs on the model.
     * @param class-string<IconImageValidator> $validator The validator for uploads.
     * @return Endpoint\Endpoint[]
     */
    public static function endpoints(string $ability = 'edit', string $validator = IconImageValidator::class): array
    {
        return [
            Endpoint\Endpoint::make('iconImage.upload')
                ->route('POST', '/{id}/icon-image')
                ->authenticated()
                ->can($ability)
                ->action(function (Context $context) use ($validator) {
                    /** @var AbstractModel $model */
                    $model = $context->model;

                    $file = Arr::get($context->request->getUploadedFiles(), 'iconImage');

                    if (! $file instanceof UploadedFileInterface) {
                        $translator = resolve(TranslatorInterface::class);

                        throw new ValidationException([
                            'iconImage' => str_replace(':attribute', 'iconImage', $translator->trans('validation.required')),
                        ]);
                    }

                    $image = resolve($validator)->validateAndRead('iconImage', $file);

                    $events = resolve(Dispatcher::class);
                    $events->dispatch(new IconImageSaving($model, $context->getActor(), $image));

                    $uploader = static::uploader($model);
                    $uploader->upload($model, $image);

                    static::save($model, $uploader);

                    $events->dispatch(new IconImageChanged($model, $context->getActor()));

                    return $model;
                }),
            Endpoint\Endpoint::make('iconImage.delete')
                ->route('DELETE', '/{id}/icon-image')
                ->authenticated()
                ->can($ability)
                ->action(function (Context $context) {
                    /** @var AbstractModel $model */
                    $model = $context->model;

                    $events = resolve(Dispatcher::class);
                    $events->dispatch(new IconImageSaving($model, $context->getActor(), null));

                    $uploader = static::uploader($model);
                    $uploader->remove($model);

                    static::save($model, $uploader);

                    $events->dispatch(new IconImageChanged($model, $context->getActor()));

                    return $model;
                }),
        ];
    }

    /**
     * The model's uploader: HasIconImage::iconImageUploader().
     */
    protected static function uploader(AbstractModel $model): IconImageUploader
    {
        return method_exists($model, 'iconImageUploader')
            ? $model->iconImageUploader()
            : resolve(IconImageUploader::class);
    }

    /**
     * Save the model, undoing the uploader's change if that fails, including
     * when a `saving` or `updating` listener cancels it by returning false.
     */
    protected static function save(AbstractModel $model, IconImageUploader $uploader): void
    {
        try {
            $saved = $model->save();
        } catch (Throwable $e) {
            $uploader->revert($model);

            throw $e;
        }

        if (! $saved) {
            $uploader->revert($model);

            throw new RuntimeException('Saving the model was cancelled.');
        }
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
