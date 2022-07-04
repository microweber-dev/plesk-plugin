import { useState } from 'react';
import { Button, Dialog, Paragraph, FormFieldText, Link } from '@plesk/ui-library';

const [isOpen, setOpen] = useState(false);

const handleClose = () => setOpen(false);

<div>
    <Button onClick={() => setOpen(true)}>Open Dialog</Button>
    <Dialog
        isOpen={isOpen}
        title="Subscribe to our newsletters"
        subtitle="Some dialog subtitle"
        banner={<img src="Dialog/dialog-banner.png" alt="" />}
        size="sm"
        onClose={handleClose}
        form={{
            onSubmit: handleClose,
            submitButton: { children: 'Subscribe' },
            cancelButton: { children: 'No, thanks' },
        }}
    >
        <Paragraph>
            Would you like to receive security-related, technical and general product information in
            your personal Plesk Newsletter?
        </Paragraph>
        <FormFieldText name="name" label="Your email address" size="fill" required />
        <Paragraph>
            By clicking the &quot;Subscribe&quot; button below, I agree to receiving personalized
            Plesk newsletters. Plesk International GmbH and its affiliates may store and process the
            data I provide for the purpose of delivering the newsletter according to the{' '}
            <Link href="https://www.plesk.com/">Plesk Privacy Policy</Link>.
        </Paragraph>
    </Dialog>
</div>;