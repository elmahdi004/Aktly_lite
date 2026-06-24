<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Demande;
use Illuminate\Support\Facades\Auth;
use App\Mail\SupportTicketMail;
use Illuminate\Support\Facades\Mail;
class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Ticket::with(['demande','demande.user'])->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request)
    {
        $filePaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
            // stocke chaque fichier dans "storage/app/tickets_attachments"
            $path = $file->store('tickets_attachments');
            $filePaths[] = $path;
        }
        }

        $demandeId = $request->demande_id;
        if (empty($demandeId) && Auth::check()) {
            $demandeId = Demande::where('user_id', Auth::id())
                ->latest('created_at')
                ->value('id');
        }
        if (empty($demandeId)) {
            return response()->json([
                'error' => 'Aucune demande associee au ticket. Veuillez choisir une demande.',
            ], 422);
        }

        $ticket = Ticket::create([
            'demande_id' => $demandeId,
            'type' => $request->type,
            'description' => $request->description,
            'nom' => $request->nom,
            'email' => $request->email,
            'file_path' => json_encode($filePaths),
        ]);   
        Mail::to('support@legakte.be')->send(new SupportTicketMail($ticket)); 
        return response()->json(['ticket_id'=>$ticket->id,'ticket'=>$ticket]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        //
    }
    public function lastTickets(){
        $demandes = Ticket::with(['demande.user'])->orderBy('created_at','desc')->take(5)->get();
        return response()->json($demandes);
    }
    public function getMyTickets(){
        return Ticket::whereHas('demande', function ($query) {
            $query->where('user_id', Auth::id());
        })->with('demande.user')->get();
    }
}
