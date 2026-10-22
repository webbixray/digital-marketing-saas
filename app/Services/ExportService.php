<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export data to CSV.
     */
    public function exportCsv(array $headers, array $data, string $filename): StreamedResponse
    {
        $callback = function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($data as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export posts to CSV.
     */
    public function exportPostsCsv(array $posts): StreamedResponse
    {
        $headers = ['ID', 'Platform', 'Content', 'Status', 'Scheduled At', 'Published At', 'Views', 'Likes', 'Comments', 'Shares'];
        $data = $posts->map(fn ($post) => [
            $post->id,
            $post->platform,
            Str::limit($post->content, 100),
            $post->status,
            $post->scheduled_at?->format('Y-m-d H:i'),
            $post->published_at?->format('Y-m-d H:i'),
            $post->views_count,
            $post->likes_count,
            $post->comments_count,
            $post->shares_count,
        ])->toArray();

        return $this->exportCsv($headers, $data, 'posts-export-'.date('Y-m-d').'.csv');
    }

    /**
     * Export clients to CSV.
     */
    public function exportClientsCsv(array $clients): StreamedResponse
    {
        $headers = ['ID', 'Name', 'Email', 'Company', 'Industry', 'Status', 'Created At'];
        $data = $clients->map(fn ($client) => [
            $client->id,
            $client->name,
            $client->email,
            $client->company,
            $client->industry,
            $client->status,
            $client->created_at?->format('Y-m-d'),
        ])->toArray();

        return $this->exportCsv($headers, $data, 'clients-export-'.date('Y-m-d').'.csv');
    }
}
