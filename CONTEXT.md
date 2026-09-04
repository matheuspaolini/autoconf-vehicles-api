# Domain Context

## Vehicle

A sale vehicle managed by one User. A Vehicle may have one Vehicle Gallery.

## Vehicle Gallery

The ordered collection of Vehicle Images belonging to one Vehicle.

A Vehicle Gallery may be empty and contains at most 20 Vehicle Images. When
non-empty, exactly one Vehicle Image is the cover. The oldest remaining image
becomes the cover when the current cover is removed.

## Vehicle Image

A stored image belonging to exactly one Vehicle.

## Cover

The Vehicle Image selected to represent its Vehicle in listings and details.

## Estoque compartilhado

The Vehicle catalog visible to every authenticated User.

## Meus veículos

The personal catalog view containing only Vehicles whose Proprietário is the
authenticated User.

## Proprietário

The User assigned to a Vehicle when it is registered (`vehicles.user_id`).
This is distinct from the creator and updater recorded in the Vehicle audit.
