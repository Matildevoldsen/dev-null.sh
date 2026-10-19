import { LinkifiedText } from '@/components/linkified-text';

export function ProfileBio({ bio }: { bio: string | null }) {
    if (!bio) {
        return null;
    }

    return (
        <p className="mt-3 max-w-prose text-[15px] leading-relaxed wrap-break-word whitespace-pre-wrap">
            <LinkifiedText text={bio} mentions={null} />
        </p>
    );
}
