export default function() {
  this.transition(
    this.fromRoute('discussions-sidebar'),
    this.toRoute('discussion-sidebar'),
    this.use('slideLeft')
  );
  this.transition(
    this.fromRoute('discussions'),
    this.toRoute('discussion'),
    this.use('slideLeft')
  );
}
