<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionResultPublication;
use App\Models\User;

class MemberResultArchiveService
{
    public function forMember(Competition $competition, User $user): array
    {
        $publication = CompetitionResultPublication::query()->currentPublic()->where('competition_id', $competition->id)->first();
        $record = $publication ? collect($publication->snapshot['member_entries'] ?? [])->firstWhere('user_id', $user->id) : null;
        $presentation = app(CompetitionResultPresentationService::class);
        $results = collect($publication?->snapshot['results'] ?? []);
        $assets = $publication?->assets->keyBy('source_photo_id') ?? collect();
        $photos = collect($record['photos'] ?? [])->map(function ($photo) use ($publication, $competition, $results, $assets, $presentation, $user) {
            $photo['category_name'] = $presentation->translated($photo['category']);
            $photo['capture_device_name'] = $presentation->translated($photo['capture_device'] ?? []);
            $photo['scorecards'] = app(MemberScorecardService::class)->localized([$photo['photo_id'] => $photo['scorecards'] ?? []])[$photo['photo_id']];
            $asset = $assets->get($photo['photo_id']);
            $photo['image_url'] = $asset ? ($asset->owner_user_id === $user->id ? route('competitions.result-photos.show', [$competition, $publication, $photo['photo_id']]) : ($asset->is_public ? route('result.publications.photos.show', [$publication, $photo['photo_id']]) : null)) : null;
            $result = $results->firstWhere('photo_id', $photo['photo_id']);
            $photo['result'] = $result;
            $photo['award_names'] = collect($result['awards'] ?? [])->map(fn ($award) => $presentation->translated($award['name']))->filter()->join(' · ');

            return $photo;
        });

        return [
            'competition' => $competition,
            'publication' => $publication,
            'has_record' => $record !== null,
            'name' => $publication ? $presentation->translated(data_get($publication->snapshot, 'competition.name', [])) : $competition->name,
            'photos' => $photos,
        ];
    }

    public function resultList(Competition $competition, User $user): array
    {
        $archive = $this->forMember($competition, $user);
        $publication = $archive['publication'];
        $assets = $publication?->assets->keyBy('source_photo_id') ?? collect();
        $presentation = app(CompetitionResultPresentationService::class);
        $rows = collect($publication?->snapshot['results'] ?? [])->map(function ($row) use ($publication, $assets, $competition, $user, $presentation) {
            $asset = $assets->get($row['photo_id']);
            $row['image_url'] = $asset ? ($asset->is_public
                ? route('result.publications.photos.show', [$publication, $row['photo_id']])
                : ($asset->owner_user_id === $user->id ? route('competitions.result-photos.show', [$competition, $publication, $row['photo_id']]) : null)) : null;
            $row['category_name'] = $presentation->translated($row['category']);
            $row['award_names'] = collect($row['awards'])->map(fn ($award) => $presentation->translated($award['name']))->filter()->join(' · ');
            unset($row['participant'], $row['participant_id']);

            return $row;
        })->sortBy(fn ($row) => sprintf('%s-%08d', $row['category_id'], $row['rank']));

        return ['publication' => $publication, 'name' => $archive['name'], 'has_record' => $archive['has_record'], 'rows' => $rows];
    }
}
