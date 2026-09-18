<x-error-page code="403" title="Access Denied">
    {{ $exception->getMessage() ?: __("You don't have permission to view this page.") }}
</x-error-page>
