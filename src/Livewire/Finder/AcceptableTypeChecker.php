<?php

namespace Cabinet\Filament\Livewire\Finder;

use Cabinet\FileType;
use Cabinet\Types\Contracts\HasMime;
use Illuminate\Support\Collection;

class AcceptableTypeChecker
{
    /**
     * @var Collection<FileType>
     */
    public readonly Collection $acceptableTypes;
    public function __construct(
        /**
         * @var FileTypeDto[]
         */
        array $acceptableTypes,
    )
    {
        $this->acceptableTypes = collect($acceptableTypes)
            ->map(fn (FileTypeDto $type) => $type->toFileType());
    }

    /**
     * This method checks if the given type is accepted. If applicable
     * it also checks the mime types enclosed within the FileType (HasMime/WithMime)
     */
    public function isAccepted(FileType $type)
    {
        return $this->acceptableTypes
            ->contains(function (FileType $acceptedType) use ($type) {
                // If the types are not the same, then we don't need to check
                // further and can continue to the next
                if ($acceptedType->slug() !== $type->slug()) {
                    return false;
                }

                $acceptedTypeHasMime = in_array(HasMime::class, class_implements($acceptedType))
                    && $acceptedType->getMime() !== null;

                $typeHasMime = in_array(HasMime::class, class_implements($type))
                    && $type->getMime() !== null;

                // If the accepted type is not limited to a mime type, then we
                // we know that our type is accepted.
                if (!$acceptedTypeHasMime) {
                    return true;
                }

                // If the accepted type is limited to a mime type, but our type
                // does not have a mime type, then we know that our type is not
                // accepted.
                if ($acceptedTypeHasMime && !$typeHasMime) {
                    return false;
                }

                /**
                 * @var FileType&HasMime $acceptedType
                 */

                /**
                 * @var FileType&HasMime $type
                 */

                // Since both types have a mime type, we can check if the mime
                // type is the same.
                return $acceptedType->getMime() === $type->getMime();
            });
    }
}
