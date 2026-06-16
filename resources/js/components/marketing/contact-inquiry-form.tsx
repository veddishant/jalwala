import { Form, usePage } from '@inertiajs/react';
import ContactInquiryController from '@/actions/App/Http/Controllers/ContactInquiryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type InquiryTypeOption = {
    value: string;
    label: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-11 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

const defaultTypes: InquiryTypeOption[] = [
    { value: 'supplier', label: 'Become a supplier' },
    { value: 'tenant', label: 'New tenant / partnership' },
    { value: 'bug', label: 'Report a bug' },
    { value: 'suggestion', label: 'Feature suggestion' },
    { value: 'general', label: 'General inquiry' },
];

export function ContactInquiryForm() {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;

    return (
        <div className="rounded-3xl border border-border/60 bg-card/80 p-6 shadow-lg backdrop-blur md:p-8">
            {flash?.status && (
                <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {flash.status}
                </div>
            )}

            <Form
                {...ContactInquiryController.store.form()}
                resetOnSuccess={['message', 'subject']}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Full name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="name"
                                    className="min-h-11"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    className="min-h-11"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone (optional)</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    autoComplete="tel"
                                    className="min-h-11"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Inquiry type</Label>
                                <select
                                    id="type"
                                    name="type"
                                    required
                                    defaultValue="supplier"
                                    className={selectClassName}
                                >
                                    {defaultTypes.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="subject">Subject (optional)</Label>
                            <Input
                                id="subject"
                                name="subject"
                                placeholder="Brief summary of your message"
                                className="min-h-11"
                            />
                            <InputError message={errors.subject} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="message">Message</Label>
                            <textarea
                                id="message"
                                name="message"
                                required
                                rows={5}
                                placeholder="Tell us about your water delivery business, issue, or idea..."
                                className="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-[8rem] w-full resize-y rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                            />
                            <InputError message={errors.message} />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="min-h-11 w-full sm:w-auto"
                        >
                            Send message
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
