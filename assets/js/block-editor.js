( function( wp ) {
    const { __ } = wp.i18n;
    const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const { Button, PanelRow, TextControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { registerPlugin } = wp.plugins;

    const SuperSwankFeaturedImageSettings = () => {
        const [defaultImage, setDefaultImage] = useState(0);
        const [imageUrl, setImageUrl] = useState('');

        // Get the current default image ID from WordPress settings
        const currentDefaultImage = useSelect(select => {
            const settings = select('core').getEntityRecord('root', 'site');
            return settings?.ssfi_default_image || 0;
        }, []);

        // Update state when settings change
        useEffect(() => {
            if (currentDefaultImage !== defaultImage) {
                setDefaultImage(currentDefaultImage);
                if (currentDefaultImage) {
                    // Fetch the image URL
                    wp.media.attachment(currentDefaultImage).fetch().then(() => {
                        setImageUrl(wp.media.attachment(currentDefaultImage).get('url'));
                    });
                }
            }
        }, [currentDefaultImage]);

        const { saveEntityRecord } = useDispatch('core');

        const onSelectImage = (media) => {
            setDefaultImage(media.id);
            setImageUrl(media.url);
            saveEntityRecord('root', 'site', {
                ssfi_default_image: media.id
            });
        };

        const removeImage = () => {
            setDefaultImage(0);
            setImageUrl('');
            saveEntityRecord('root', 'site', {
                ssfi_default_image: 0
            });
        };

        return (
            <PanelRow>
                <div className="ssfi-settings-content" style={{ width: '100%' }}>
                    <TextControl
                        label={__('Default Featured Image', 'super-swank-featured-image')}
                        help={__('This image will be used when no featured image is set for a post or page.', 'super-swank-featured-image')}
                    />
                    <div style={{ marginTop: '1em' }}>
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={onSelectImage}
                                allowedTypes={['image']}
                                value={defaultImage}
                                render={({ open }) => (
                                    <div style={{ marginBottom: '1em' }}>
                                        {defaultImage && imageUrl ? (
                                            <div>
                                                <img
                                                    src={imageUrl}
                                                    alt=""
                                                    style={{
                                                        maxWidth: '100%',
                                                        marginBottom: '1em',
                                                        display: 'block'
                                                    }}
                                                />
                                                <div style={{ marginBottom: '1em' }}>
                                                    <Button
                                                        onClick={open}
                                                        variant="secondary"
                                                        style={{ marginRight: '0.5em' }}
                                                    >
                                                        {__('Replace Image', 'super-swank-featured-image')}
                                                    </Button>
                                                    <Button
                                                        onClick={removeImage}
                                                        variant="secondary"
                                                        isDestructive
                                                    >
                                                        {__('Remove Image', 'super-swank-featured-image')}
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <Button
                                                onClick={open}
                                                variant="primary"
                                                className="editor-post-featured-image__toggle"
                                            >
                                                {__('Select Default Image', 'super-swank-featured-image')}
                                            </Button>
                                        )}
                                    </div>
                                )}
                            />
                        </MediaUploadCheck>
                    </div>
                </div>
            </PanelRow>
        );
    };

    if (wp.customize) {
        wp.customize.sectionConstructor['super-swank-featured-image'] = wp.customize.Section.extend({
            template: wp.template('super-swank-featured-image-section')
        });
    }

    wp.domReady(() => {
        const settings = document.querySelector('.edit-site-global-styles-sidebar');
        if (settings) {
            const section = document.createElement('div');
            section.className = 'edit-site-global-styles-section super-swank-featured-image-section';
            wp.element.render(<SuperSwankFeaturedImageSettings />, section);
            settings.appendChild(section);
        }
    });
} )( window.wp ); 