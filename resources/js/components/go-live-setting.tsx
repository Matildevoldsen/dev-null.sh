import { Form, router } from '@inertiajs/react';
import { Radio, Square } from 'lucide-react';
import UserLiveController from '@/actions/App/Http/Controllers/UserLiveController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LiveBadge } from '@/components/live-badge';
import { Button } from '@/components/ui/button';
import { useUser } from '@/hooks/use-user';

export function GoLiveSetting() {
    const { user } = useUser();
    const liveUrl = user.live_url ?? null;

    return (
        <section className="px-4 py-6 sm:px-6">
            <Heading
                title="Go live"
                description="Streaming on YouTube or Twitch? Share the link and your profile plays it, with a live marker on your avatar in the log. It ends on its own after 12 hours."
            />

            {liveUrl === null ? (
                <Form
                    {...UserLiveController.update.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => router.flushAll()}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="url"
                                name="url"
                                required
                                aria-label="Stream link"
                                placeholder="https://www.twitch.tv/you"
                                className="flex h-9 w-full max-w-md min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                                data-test="live-url-input"
                            />
                            <InputError message={errors.url} className="mt-2" />
                            <Button
                                type="submit"
                                className="mt-5"
                                disabled={processing}
                                data-test="go-live-button"
                            >
                                <Radio />
                                Go live
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <Form
                    {...UserLiveController.destroy.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => router.flushAll()}
                >
                    {({ processing }) => (
                        <>
                            <p className="flex min-w-0 items-center gap-2 text-sm">
                                <LiveBadge />
                                <a
                                    href={liveUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="truncate text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                >
                                    {liveUrl}
                                </a>
                            </p>
                            <Button
                                type="submit"
                                variant="outline"
                                className="mt-5"
                                disabled={processing}
                                data-test="end-live-button"
                            >
                                <Square />
                                End stream
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </section>
    );
}
