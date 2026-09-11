<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'commentable_type' => 'nullable|string|max:255',
            'commentable_id' => 'nullable|integer',
        ]);

        $agencyId = $request->user()->agency_id;
        $commentableType = $request->input('commentable_type');
        $commentableId = $request->input('commentable_id');

        $query = Comment::where('agency_id', $agencyId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($commentableType && $commentableId) {
            $query->where('commentable_type', $commentableType)
                ->where('commentable_id', $commentableId);
        }

        $comments = $query->paginate(20);

        return response()->json($comments);
    }

    public function show(Request $request, Comment $comment)
    {
        if ((int) $comment->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
        return response()->json($comment->load('user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $agencyId = $request->user()->agency_id;

        $comment = Comment::create([
            'agency_id' => $agencyId,
            'user_id' => $request->user()->id,
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'body' => $request->body,
            'parent_id' => $request->parent_id,
        ]);

        return redirect('/comments')->with('success', 'Comment added.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ((int) $comment->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $comment->delete();

        return redirect('/comments')->with('success', 'Comment deleted.');
    }
}
