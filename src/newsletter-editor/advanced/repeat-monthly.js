/**
 * WordPress dependencies
 */
import { Component, Fragment } from "@wordpress/element";
import { Spinner, RadioControl } from "@wordpress/components";

class RepeatMonthly extends Component {
    constructor(props) {
        super(props);
        this.state = {
            list: [],
            loading: true
        };
    }

    componentDidMount() {
        this.runApiFetch();
    }

    runApiFetch() {
        const { getCurrentPostId } = wp.data.select("core/editor");
        const postId = getCurrentPostId();
        wp.apiFetch({
            path: `/rrze-newsletter/v1/repeat/monthly/${postId}`
        }).then(data => {
            this.setState({
                list: data,
                loading: false
            });
        });
    }

    render() {
        const { rrze_newsletter_recurrence_monthly, updateRecurrenceMonthly } =
            this.props;

        return (
            <Fragment>
                {this.state.loading ? (
                    <Spinner />
                ) : (
                    <RadioControl
                        selected={rrze_newsletter_recurrence_monthly}
                        options={JSON.parse(this.state.list)}
                        onChange={value => updateRecurrenceMonthly(value)}
                    />
                )}
            </Fragment>
        );
    }
}

export default RepeatMonthly;
