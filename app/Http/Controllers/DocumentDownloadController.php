<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Scarica il file allegato a un Documento.
     *
     * Se esiste una copia locale la serve direttamente, altrimenti rimanda all'URL
     * del documento (document_url o metadata.web_url): l'utente non deve sapere
     * dove si trova fisicamente il file.
     *
     * La rotta e' protetta dal middleware 'auth': in precedenza il download era
     * pubblico e permetteva di enumerare gli allegati (anche di reclami e SOS)
     * conoscendone l'id.
     */
    public function __invoke(Document $document): BinaryFileResponse|RedirectResponse
    {
        abort_unless(\checkPiano('documents'), 403);

        $media = $document->getFirstMedia('documents');

        if ($media !== null && is_file($media->getPath())) {
            return response()->download($media->getPath(), $media->file_name);
        }

        $url = $document->resolved_url;

        abort_if(blank($url), 404);

        return redirect()->away(str_starts_with($url, 'http') ? $url : "https://{$url}");
    }
}
